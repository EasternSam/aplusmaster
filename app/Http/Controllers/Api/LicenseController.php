<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\License;

class LicenseController extends Controller
{
    /**
     * Valida una licencia entrante desde una instalación del SGA cliente.
     */
    public function validateLicense(Request $request)
    {
        // 1. Validar que vengan los datos requeridos
        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'required|string',
        ]);

        // 2. Buscar la licencia en la base de datos CON SU PAQUETE
        $license = License::where('license_key', $request->license_key)
                          ->with('package')
                          ->first();

        // 3. Validar existencia
        if (!$license) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Licencia no encontrada en el servidor central.'
            ], 404);
        }

        // 4. Validar estado (Si la suspendiste por falta de pago)
        if (!$license->is_active) {
            return response()->json([
                'status' => 'error', 
                'message' => 'La licencia ha sido suspendida. Contacte a soporte.'
            ], 403);
        }

        // 5. Validar fecha de expiración
        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            return response()->json([
                'status' => 'error', 
                'message' => 'La licencia ha expirado.'
            ], 403);
        }

        // 6. Validar y Registrar Dominio (Previene Piratería)
        if (empty($license->registered_domain)) {
            $license->registered_domain = $request->domain;
            $license->save();
        } elseif ($license->registered_domain !== $request->domain) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Esta licencia ya está en uso en un dominio diferente (' . $license->registered_domain . ').'
            ], 403);
        }

        // --- LÓGICA DE PAQUETES Y FUNCIONES ---
        
        // A. Obtener funciones base del paquete asignado
        $features = $license->package ? ($license->package->features ?? []) : [];

        // B. Mezclar con funciones custom (excepciones para este cliente)
        // Esto permite activar funciones extra a un cliente específico sin cambiarlo de plan
        if ($license->custom_features) {
            // array_merge sobreescribe o añade. array_unique quita duplicados.
            $features = array_values(array_unique(array_merge($features, $license->custom_features)));
        }

        // Todo está perfecto
        return response()->json([
            'status' => 'success', 
            'message' => 'Licencia válida y operativa.',
            'data' => [
                'plan_name' => $license->package->name ?? 'Personalizado',
                'features'  => $features, // Enviamos la lista de permisos: ['finance', 'academic', etc]
            ]
        ], 200);
    }
}