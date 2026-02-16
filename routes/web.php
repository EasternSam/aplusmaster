<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;
use App\Livewire\FeatureManager; // Importamos el componente de Addons

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// --- RUTAS DE GESTIÓN (Protegidas) ---
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Ruta para el Gestor de Addons/Módulos
    Route::get('/features', FeatureManager::class)->name('features');

});
    
// --- API DE LICENCIAMIENTO (Pública para los clientes) ---
// Movemos la ruta API a web.php para evitar problemas de Laravel 11 en cPanel
// y le quitamos el middleware CSRF para que permita conexiones externas.
Route::post('/api/v1/validate-license', [LicenseController::class, 'validateLicense'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__.'/auth.php';