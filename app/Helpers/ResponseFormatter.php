<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ResponseFormatter
{
    /**
     * Định dạng response thành công.
     *
     * @param mixed $data Dữ liệu trả về
     * @param string $message Thông điệp mô tả
     * @param int $statusCode Mã trạng thái HTTP
     * @return JsonResponse
     */
    public static function success($data = null, string $message = 'Thành công', int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Định dạng response thất bại.
     *
     * @param string $message Thông điệp lỗi
     * @param mixed $data Dữ liệu trả về (nếu có)
     * @param int $statusCode Mã trạng thái HTTP
     * @return JsonResponse
     */
    public static function fail(string $message = 'Thất bại', $data = null, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'status' => 'fail',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}