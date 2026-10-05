<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * 只負責 HTTP 進出，帳號規則在 App\Services\AuthService。
 */
class AuthController
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register(
            name: $request->string('name')->toString(),
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        );

        return response()->json($result, JsonResponse::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // 密碼錯誤時 AuthService 會拋出例外並自行轉成 401。
        return response()->json($this->auth->login(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
        ));
    }

    /**
     * #[CurrentUser] 由容器直接注入當前使用者，不必經過 $request->user()。
     */
    public function logout(#[CurrentUser] User $user): JsonResponse
    {
        $this->auth->logout($user);

        return response()->json(['message' => '已登出。']);
    }

    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return response()->json($user);
    }
}
