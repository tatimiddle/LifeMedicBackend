<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Devuelve una respuesta JSON estandarizada de éxito.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operación exitosa.',
        int $status = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        $payload = [
            'success' => true,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Devuelve una respuesta JSON estándar 201 Created.
     */
    public static function created(
        mixed $data = null,
        string $message = 'Recurso creado correctamente.',
    ): JsonResponse {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Devuelve una respuesta JSON estandarizada de error.
     */
    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
