<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\License;
use Illuminate\Support\Facades\Log;

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        // LOG PARA DEBUGGING: Ver qué llega
        Log::info("Validación de Licencia Solicitada", $request->all());

        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'required|string',
        ]);

        $license = License::where('license_key', $request->license_key)
                          ->with('package')
                          ->first();

        if (!$license) {
            Log::warning("Licencia no encontrada: " . $request->license_key);
            return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada.'], 404);
        }

        // Verificar estado
        if (!$license->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Licencia suspendida.'], 403);
        }
        
        // Verificar expiración
        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'Licencia expirada.'], 403);
        }

        // LÓGICA DE DOMINIO (Auto-registro en primer uso)
        if (empty($license->registered_domain)) {
            // Si es la primera vez que se usa, registramos el dominio del solicitante
            $license->registered_domain = $request->domain;
            $license->save();
            Log::info("Licencia {$license->license_key} vinculada al dominio {$request->domain}");
        } elseif ($license->registered_domain !== $request->domain) {
            // Si ya tiene dominio y no coincide con el solicitante
            Log::error("Intento de uso de licencia en dominio incorrecto. Registrado: {$license->registered_domain}, Solicitante: {$request->domain}");
            return response()->json([
                'status' => 'error', 
                'message' => "Dominio no autorizado. Esta licencia pertenece a: {$license->registered_domain}"
            ], 403);
        }

        // Preparar características (features)
        $features = [];
        if (!is_null($license->custom_features)) {
            $features = $license->custom_features;
        } else {
            $features = $license->package ? ($license->package->features ?? []) : [];
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'Licencia válida.',
            'data' => [
                'plan_name' => $license->package->name ?? 'Personalizado',
                'features'  => $features,
            ]
        ], 200);
    }
}