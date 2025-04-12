<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SwaggerJsonController;

Route::get('/', function () {
    return view('welcome');
});

// Swagger documentation
Route::get('/api/swagger.json', [SwaggerJsonController::class, 'index'])
    ->name('swagger.json')
    ->middleware('api');
