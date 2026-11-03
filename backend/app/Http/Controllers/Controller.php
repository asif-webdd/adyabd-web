<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

abstract class Controller
{
    /**
     * success response method.
     *
     * @return JsonResponse
     */
    public function success(mixed $data, string $message = 'Success', int $code = 200)
    {
        $response = [
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ];

        return response()->json($response, $code);
    }

    /**
     * return error response.
     *
     * @return JsonResponse
     */
    public function error($error, $errorMessages = [], $code = 404)
    {
        // Most callers write error($msg, 422): the 2nd argument is really the HTTP status.
        if (is_int($errorMessages)) {
            $code = $errorMessages;
            $errorMessages = [];
        }

        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
