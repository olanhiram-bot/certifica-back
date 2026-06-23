<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(object $data, string $message = '', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'Success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message, int $code): JsonResponse
    {
        return response()->json([
            'status' => 'Error',
            'message' => $message,
            'code' => $code,
        ], $code);
    }
}
