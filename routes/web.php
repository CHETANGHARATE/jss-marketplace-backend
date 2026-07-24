<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'JSS Solutions Marketplace API',
        'status' => 'online',
        'version' => '1.0.0',
    ]);
});
