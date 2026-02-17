<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ruta pública para validación de licencias desde SGA PADRE
Route::post('/v1/validate-license', [LicenseController::class, 'validateLicense']);