<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 預約已經處於取消狀態。由 Service 層拋出，自行轉成 409 Conflict。
 */
class AppointmentAlreadyCancelledException extends Exception
{
    protected $message = '此預約已經被取消。';

    public function render(Request $request): JsonResponse
    {
        return response()->json(
            ['message' => $this->getMessage()],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
