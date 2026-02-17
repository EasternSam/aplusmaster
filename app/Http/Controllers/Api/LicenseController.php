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
        try {
            $request->validate([
                'license_key' => 'required|string',
                'domain'      => 'required|string',
            ]);

            // Normalizar dominio entrante
            $incomingDomain = $this->normalizeDomain($request->domain);

            Log::info("MASTER LICENCIA: Validando...", [
                'key' => $request->license_key,
                'dominio_recibido' => $incomingDomain,
            ]);

            // --- ZONA DE RIESGO: CONSULTA A BASE DE DATOS ---
            try {
                $license = License::where('license_key', $request->license_key)
                                  ->with('package')
                                  ->first();
            } catch (\Throwable $e) {
                Log::error("MASTER LICENCIA: ¡ERROR FATAL DE BD!", [
                    'mensaje' => $e->getMessage(),
                    'archivo' => $e->getFile(),
                    'linea' => $e->getLine()
                ]);
                return response()->json(['status' => 'error', 'message' => 'Error interno del servidor de licencias (BD).'], 500);
            }
            // ------------------------------------------------

            if (!$license) {
                Log::warning("MASTER LICENCIA: No encontrada -> " . $request->license_key);
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada.'], 404);
            }

            if (!$license->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Licencia suspendida.'], 403);
            }
            
            if ($license->expires_at && now()->greaterThan($license->expires_at)) {
                return response()->json(['status' => 'error', 'message' => 'Licencia expirada.'], 403);
            }

            $registeredDomain = $this->normalizeDomain($license->registered_domain);

            // LÓGICA DE DOMINIO
            if (empty($registeredDomain)) {
                $license->registered_domain = $incomingDomain;
                $license->save();
                Log::info("MASTER LICENCIA: Auto-registrado dominio {$incomingDomain}");
            } elseif ($registeredDomain !== $incomingDomain) {
                Log::warning("MASTER LICENCIA: Dominio incorrecto.", [
                    'esperado' => $registeredDomain,
                    'recibido' => $incomingDomain
                ]);
                return response()->json([
                    'status' => 'error', 
                    'message' => "Dominio no autorizado. Registrado para: {$license->registered_domain}"
                ], 403);
            }

            // Features
            $features = !is_null($license->custom_features) 
                ? $license->custom_features 
                : ($license->package ? ($license->package->features ?? []) : []);

            return response()->json([
                'status' => 'success', 
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => $license->package->name ?? 'Personalizado',
                    'features'  => $features,
                ]
            ], 200);

        } catch (\Throwable $e) {
            // Captura errores generales fuera de la BD (ej: validación, lógica)
            Log::error("MASTER LICENCIA: Error general: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error inesperado en el servidor.'], 500);
        }
    }

    private function normalizeDomain($url)
    {
        if (empty($url)) return null;
        $domain = preg_replace('#^https?://#', '', $url);
        $domain = preg_replace('#^www\.#', '', $domain);
        return rtrim($domain, '/');
    }
}