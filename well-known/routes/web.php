<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LicenseController;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');
    
// Movemos la ruta API a web.php para evitar problemas de Laravel 11 en cPanel
// y le quitamos el middleware CSRF para que permita conexiones externas.
Route::post('/api/v1/validate-license', [LicenseController::class, 'validateLicense'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

require __DIR__.'/auth.php';
