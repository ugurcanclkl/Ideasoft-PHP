<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class BaseController extends Controller
{
    /**
     * Return a success JSON response.
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success($data = null, string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response.
     *
     * @param string|null $message
     * @param array|null $errors
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message = null, array $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
