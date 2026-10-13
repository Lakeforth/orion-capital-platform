<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    public function successResponse($message, $data = [], $code = 200):JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if(!empty($data)){
            $response['data'] = $data;
        }else{
            $response['data'] = [];
        }


        return response()->json($response, $code);
    }

    public function errorResponse($error, $code = 404, $error_type = 'error'): JsonResponse
    {

        return response()->json([
            'status' => 'failed',
            'error' => $error,
            'message' => $error,
            'error_type' => $error_type
        ], $code);
    }
}
