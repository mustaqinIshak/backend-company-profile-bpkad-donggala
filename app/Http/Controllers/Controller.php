<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        $response = ['success' => true, 'message' => $message];
        if (!is_null($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $code);
    }

    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        $response = ['success' => false, 'message' => $message];
        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }
        return response()->json($response, $code);
    }
}
