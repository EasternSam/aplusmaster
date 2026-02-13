<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\License;

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'required|string',
        ]);

        $license = License::where('license_key', $request->license_key)
                          ->with('package')
                          ->first();

        if (!$license) return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada.'], 404);
        if (!$license->is_active) return response()->json(['status' => 'error', 'message' => 'Licencia suspendida.'], 403);
        
        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'Licencia expirada.'], 403);
        }

        if (empty($license->registered_domain)) {
            $license->registered_domain = $request->domain;
            $license->save();
        } elseif ($license->registered_domain !== $request->domain) {
            return response()->json(['status' => 'error', 'message' => 'Dominio no autorizado.'], 403);
        }

        // --- LÓGICA MEJORADA DE CARACTERÍSTICAS ---
        
        // 1. Si existe una lista personalizada (porque el admin movió módulos manualmente), usamos ESA.
        if (!is_null($license->custom_features)) {
            $features = $license->custom_features;
        } 
        // 2. Si no, usamos las del paquete asignado.
        else {
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