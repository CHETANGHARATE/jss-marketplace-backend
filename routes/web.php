<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => config('brand.name', 'JDS Mart') . ' API',
        'status' => 'online',
        'version' => '1.0.0',
    ]);
});
