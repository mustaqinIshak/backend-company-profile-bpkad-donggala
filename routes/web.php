<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'BPKAD Donggala API – use /api prefix for all endpoints.',
        'version' => '1.0.0',
    ]);
});
