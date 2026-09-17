<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_可以註冊並直接拿到_token(): void
    {
        $this->postJson('/api/register', [
            'name' => '王小明',
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'Str0ng-Passw0rd',
        ])
            ->assertCreated()
            ->assertJsonPath('user.email', 'ming@example.com')
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'ming@example.com']);
    }

    public function test_註冊回應不會洩漏密碼(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '王小明',
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'Str0ng-Passw0rd',
        ])->assertCreated();

        $this->assertArrayNotHasKey('password', $response->json('user'));
    }

    public function test_密碼會被雜湊而非明文存入(): void
    {
        $this->postJson('/api/register', [
            'name' => '王小明',
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'Str0ng-Passw0rd',
        ])->assertCreated();

        $user = User::where('email', 'ming@example.com')->first();

        $this->assertNotSame('Str0ng-Passw0rd', $user->password);
        $this->assertTrue(Hash::check('Str0ng-Passw0rd', $user->password));
    }

    public function test_email_重複註冊會回傳_422(): void
    {
        User::factory()->create(['email' => 'ming@example.com']);

        $this->postJson('/api/register', [
            'name' => '王小明',
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'Str0ng-Passw0rd',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_密碼確認不一致會回傳_422(): void
    {
        $this->postJson('/api/register', [
            'name' => '王小明',
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_正確帳密可以登入(): void
    {
        User::factory()->create([
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'ming@example.com')
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_密碼錯誤會回傳_401(): void
    {
        User::factory()->create([
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
        ]);

        $this->postJson('/api/login', [
            'email' => 'ming@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    /**
     * 帳號不存在與密碼錯誤必須是同一種回應，
     * 否則可以被用來列舉哪些 email 註冊過。
     */
    public function test_帳號不存在與密碼錯誤的回應一致(): void
    {
        User::factory()->create([
            'email' => 'ming@example.com',
            'password' => 'Str0ng-Passw0rd',
        ]);

        $wrongPassword = $this->postJson('/api/login', [
            'email' => 'ming@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $noSuchUser = $this->postJson('/api/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();

        $this->assertSame($wrongPassword->json(), $noSuchUser->json());
    }

    public function test_登入後可以取得自己的資料(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email);
    }

    public function test_未登入不能取得自己的資料(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_登出會撤銷當次使用的_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // 同一個測試程序裡 auth guard 會記住上一次解析出的使用者，
        // 不清掉就驗不到「token 已失效」這件事（真實環境每個請求是獨立程序）。
        $this->app['auth']->forgetGuards();

        // 同一組 token 再用就不通了。
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    /**
     * 登出只撤銷當次 token，其他裝置應該維持登入。
     */
    public function test_登出不影響其他裝置的_token(): void
    {
        $user = User::factory()->create();
        $phone = $user->createToken('api')->plainTextToken;
        $laptop = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$phone)
            ->postJson('/api/logout')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$laptop)
            ->getJson('/api/me')
            ->assertOk();
    }

    /**
     * 驗證 AppServiceProvider 裡的具名限流器真的套用在路由上。
     */
    public function test_登入失敗超過次數會被限流(): void
    {
        User::factory()->create(['email' => 'ming@example.com']);

        // 限流器設定為每分鐘 5 次，第 6 次應該被擋下。
        foreach (range(1, 5) as $ignored) {
            $this->postJson('/api/login', [
                'email' => 'ming@example.com',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/login', [
            'email' => 'ming@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    /**
     * 限流以 email + IP 為單位，不該讓某個帳號被鎖時波及其他帳號。
     */
    public function test_限流不會波及其他帳號(): void
    {
        User::factory()->create(['email' => 'ming@example.com']);
        User::factory()->create([
            'email' => 'hua@example.com',
            'password' => 'Str0ng-Passw0rd',
        ]);

        foreach (range(1, 6) as $ignored) {
            $this->postJson('/api/login', [
                'email' => 'ming@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/login', [
            'email' => 'hua@example.com',
            'password' => 'Str0ng-Passw0rd',
        ])->assertOk();
    }

    /**
     * ForceJsonResponse：不帶 Accept header 也要拿到 JSON，
     * 而不是 Laravel 預設的 302 轉址。
     */
    public function test_不帶_accept_header_仍回傳_json(): void
    {
        $response = $this->post('/api/login', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }
}
