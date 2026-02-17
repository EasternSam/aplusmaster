<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\AddonController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// --- RUTAS PÚBLICAS (SGA PADRE -> APLUSMASTER) ---
// Esta ruta DEBE ser pública para que los clientes validen su licencia.
Route::post('/v1/validate-license', [LicenseController::class, 'validateLicense']);

// Otras rutas de la API
Route::apiResource('licenses', LicenseController::class);
Route::apiResource('addons', AddonController::class);