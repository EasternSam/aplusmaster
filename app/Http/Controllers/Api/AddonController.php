<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feature;
use App\Models\License;
use Illuminate\Support\Facades\Storage;

class AddonController extends Controller
{
    /**
     * Permite a un SGA Cliente descargar el ZIP de un módulo si tiene permiso.
     */
    public function download(Request $request, $code)
    {
        // 1. Validar Credenciales (Seguridad Básica)
        $licenseKey = $request->input('license_key');
        $domain = $request->input('domain');

        if (!$licenseKey || !$domain) {
            return response()->json(['error' => 'Credenciales no proporcionadas'], 401);
        }

        // 2. Verificar Licencia
        $license = License::where('license_key', $licenseKey)
                          ->where('registered_domain', $domain)
                          ->where('is_active', true)
                          ->with('package')
                          ->first();

        if (!$license) {
            return response()->json(['error' => 'Licencia inválida o inactiva'], 403);
        }

        // 3. Verificar si tiene acceso a ESTE feature específico
        // (Combinamos features del plan + features custom)
        $planFeatures = $license->package ? ($license->package->features ?? []) : [];
        $customFeatures = $license->custom_features ?? [];
        $allFeatures = array_unique(array_merge($planFeatures, $customFeatures));

        if (!in_array($code, $allFeatures)) {
            return response()->json(['error' => "No tienes acceso al módulo: $code"], 403);
        }

        // 4. Buscar el archivo del módulo
        $feature = Feature::where('code', $code)->first();

        if (!$feature || !$feature->file_path || !Storage::exists($feature->file_path)) {
            return response()->json(['error' => 'El archivo del módulo no está disponible en el servidor maestro.'], 404);
        }

        // 5. Entregar el archivo ZIP
        return Storage::download($feature->file_path, "{$code}_module.zip");
    }
}