<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
// Importamos los modelos para intentar usar Eloquent si es posible
use App\Models\License;

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        // Log::info("DIAGNOSTICO: Inicio validación", $request->all());

        try {
            $key = $request->input('license_key');
            $domain = $request->input('domain');

            if (!$key || !$domain) {
                return response()->json(['status' => 'error', 'message' => 'Datos incompletos'], 400);
            }

            // 1. Obtener la licencia (Usando Query Builder por seguridad)
            $licenseData = DB::table('licenses')->where('license_key', $key)->first();

            if (!$licenseData) {
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada'], 404);
            }

            // 2. Validar estado
            $is_active = (bool) ($licenseData->is_active ?? 0);
            if (!$is_active) {
                return response()->json(['status' => 'error', 'message' => 'Academic+: Su licencia ha sido SUSPENDIDA o expiró.'], 403);
            }

            // 3. Validar Dominio
            $incomingDomain = $this->cleanDomain($domain);
            $storedDomain = $this->cleanDomain($licenseData->registered_domain);

            if (empty($storedDomain)) {
                DB::table('licenses')->where('id', $licenseData->id)->update(['registered_domain' => $incomingDomain]);
                Log::info("DIAGNOSTICO: Auto-registrado dominio {$incomingDomain}");
            } 
            elseif ($incomingDomain !== $storedDomain) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "Dominio no autorizado ({$incomingDomain}). Registrado para: {$storedDomain}"
                ], 403);
            }

            // 4. OBTENER CARACTERÍSTICAS (FEATURES) - LÓGICA CORREGIDA SEGÚN MIGRACIONES
            // Estructura: Licencia -> package_id (Tabla packages columna 'features' JSON)
            //           Licencia -> custom_features (JSON)
            $features = [];
            
            try {
                // A. Obtener features del Paquete base
                if (!empty($licenseData->package_id)) {
                    $package = DB::table('packages')->where('id', $licenseData->package_id)->first();
                    
                    if ($package && !empty($package->features)) {
                        // Decodificar JSON de la tabla packages
                        $packageFeatures = json_decode($package->features, true);
                        if (is_array($packageFeatures)) {
                            $features = array_merge($features, $packageFeatures);
                        }
                    }
                }

                // B. Obtener features personalizadas de la Licencia (custom_features en tabla licenses)
                if (!empty($licenseData->custom_features)) {
                    $customFeatures = json_decode($licenseData->custom_features, true);
                    if (is_array($customFeatures)) {
                        $features = array_merge($features, $customFeatures);
                    }
                }

                // Limpiar duplicados y reindexar array
                $features = array_values(array_unique($features));

            } catch (\Exception $ex) {
                Log::error("DIAGNOSTICO: Error procesando features JSON: " . $ex->getMessage());
                // No bloqueamos, enviamos array vacío si falla el JSON
            }

            // 5. RESPUESTA DE ÉXITO
            return response()->json([
                'status' => 'success',
                'valid' => true,
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => 'Standard', // Puedes obtener $package->name si lo deseas
                    'features' => $features
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error("DIAGNOSTICO: Error Fatal General: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error interno del servidor de licencias.'], 500);
        }
    }

    private function cleanDomain($url)
    {
        if (empty($url)) return null;
        $d = strtolower($url);
        $d = preg_replace('#^https?://#', '', $d);
        $d = preg_replace('#^www\.#', '', $d);
        $d = explode('/', $d)[0];
        return trim($d);
    }
}