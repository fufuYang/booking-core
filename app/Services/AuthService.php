<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * 發給前端的 token 名稱。之後要做「裝置管理」時會用到。
     */
    private const TOKEN_NAME = 'api';

    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * 註冊並直接發一組 token，省掉註冊完再登入一次。
     *
     * @return array{user: User, token: string}
     */
    public function register(string $name, string $email, string $password): array
    {
        $user = $this->users->create([
            'name' => $name,
            'email' => $email,
            // password 欄位在 User 的 casts 裡宣告為 'hashed'，這裡給明文即可。
            'password' => $password,
        ]);

        return [
            'user' => $user,
            'token' => $user->createToken(self::TOKEN_NAME)->plainTextToken,
        ];
    }

    /**
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException
     */
    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);

        // 即使查無此人也要跑一次 Hash::check，讓兩種失敗耗時接近，
        // 避免從回應時間推測 email 是否存在。
        $matches = Hash::check($password, $user?->password ?? '');

        if ($user === null || ! $matches) {
            throw new InvalidCredentialsException;
        }

        return [
            'user' => $user,
            'token' => $user->createToken(self::TOKEN_NAME)->plainTextToken,
        ];
    }

    /**
     * 只撤銷這次請求使用的 token，其他裝置維持登入。
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
