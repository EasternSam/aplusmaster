<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;
use App\Http\Controllers\Api\AddonController; // Nuevo Controller
use App\Livewire\FeatureManager;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/features', FeatureManager::class)->name('features');
});

// --- API PÚBLICA PARA CLIENTES ---
// Validación de Licencia
Route::post('/api/v1/validate-license', [LicenseController::class, 'validateLicense'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Descarga de Addons (Protegida internamente por lógica de licencia)
Route::get('/api/v1/addons/download/{code}', [AddonController::class, 'download'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__.'/auth.php';