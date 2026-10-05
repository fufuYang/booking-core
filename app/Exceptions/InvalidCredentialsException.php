<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 帳號或密碼錯誤。訊息刻意不區分「帳號不存在」與「密碼錯誤」，
 * 避免被用來探測哪些 email 有註冊過。
 */
class InvalidCredentialsException extends Exception
{
    protected $message = '帳號或密碼錯誤。';

    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_UNAUTHORIZED,
        );
    }
}
