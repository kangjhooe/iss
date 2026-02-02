<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

/**
 * Standarisasi pesan error dan response API.
 * 401: Unauthorized (authentication)
 * 403: Forbidden (authorization)
 * 404: Not found
 * 422: Validation failed
 * 500: Server error
 */
class ApiResponse
{
    public const MESSAGE_UNAUTHORIZED = 'Unauthorized';
    public const MESSAGE_FORBIDDEN = 'Forbidden';
    public const MESSAGE_NOT_FOUND = 'Tidak ditemukan';
    public const MESSAGE_VALIDATION_FAILED = 'Validasi gagal';
    public const MESSAGE_SERVER_ERROR = 'Terjadi kesalahan. Silakan coba lagi.';

    public static function unauthorized(?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? self::MESSAGE_UNAUTHORIZED,
        ], 401);
    }

    public static function forbidden(?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? self::MESSAGE_FORBIDDEN,
        ], 403);
    }

    public static function notFound(?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? self::MESSAGE_NOT_FOUND,
        ], 404);
    }

    public static function validationFailed(array $errors, ?string $message = null): JsonResponse
    {
        return response()->json([
            'message' => $message ?? self::MESSAGE_VALIDATION_FAILED,
            'errors' => $errors,
        ], 422);
    }

    public static function serverError(?string $message = null, $debugMessage = null): JsonResponse
    {
        $payload = [
            'message' => $message ?? self::MESSAGE_SERVER_ERROR,
        ];
        if (config('app.debug') && $debugMessage !== null) {
            $payload['error'] = $debugMessage;
        }
        return response()->json($payload, 500);
    }

    public static function badRequest(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 400);
    }
}
