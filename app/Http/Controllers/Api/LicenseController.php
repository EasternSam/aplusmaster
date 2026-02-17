<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\License;
use Illuminate\Support\Facades\Log; // Importante para el debug

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        // 1. LOG DE ENTRADA
        Log::info("DEBUG LICENCIA: Nueva petición recibida.", [
            'ip' => $request->ip(),
            'datos_recibidos' => $request->all()
        ]);

        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'required|string',
        ]);

        $license = License::where('license_key', $request->license_key)
                          ->with('package')
                          ->first();

        // 2. LOG DE BÚSQUEDA
        if (!$license) {
            Log::error("DEBUG LICENCIA: Licencia NO encontrada en BD.", ['key_buscada' => $request->license_key]);
            return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada.'], 404);
        }

        Log::info("DEBUG LICENCIA: Licencia encontrada.", [
            'id' => $license->id,
            'registrada_a_dominio' => $license->registered_domain,
            'estado_activo' => $license->is_active,
            'expira' => $license->expires_at
        ]);

        // Validaciones
        if (!$license->is_active) {
            Log::warning("DEBUG LICENCIA: Rechazada por estar suspendida/inactiva.");
            return response()->json(['status' => 'error', 'message' => 'Licencia suspendida.'], 403);
        }
        
        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            Log::warning("DEBUG LICENCIA: Rechazada por expiración.", ['expiró_el' => $license->expires_at]);
            return response()->json(['status' => 'error', 'message' => 'Licencia expirada.'], 403);
        }

        // Validación de Dominio
        if (empty($license->registered_domain)) {
            Log::info("DEBUG LICENCIA: Dominio vacío en BD. Auto-registrando.", ['nuevo_dominio' => $request->domain]);
            $license->registered_domain = $request->domain;
            $license->save();
        } elseif ($license->registered_domain !== $request->domain) {
            Log::error("DEBUG LICENCIA: RECHAZADA POR DOMINIO.", [
                'esperado_bd' => $license->registered_domain,
                'recibido_peticion' => $request->domain
            ]);
            return response()->json([
                'status' => 'error', 
                'message' => "Dominio no autorizado. Esta licencia pertenece a: {$license->registered_domain}"
            ], 403);
        }

        // --- LÓGICA DE CARACTERÍSTICAS ---
        
        if (!is_null($license->custom_features)) {
            $features = $license->custom_features;
        } else {
            $features = $license->package ? ($license->package->features ?? []) : [];
        }

        Log::info("DEBUG LICENCIA: Validación EXITOSA.");

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