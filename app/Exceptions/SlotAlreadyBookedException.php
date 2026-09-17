<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 時段已被預約。由 Service 層拋出，例外自己負責轉成 409，
 * Controller 不需要知道這件事該回什麼狀態碼。
 */
class SlotAlreadyBookedException extends Exception
{
    protected $message = '這個時段已經被預約了，請重新選擇。';

    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
