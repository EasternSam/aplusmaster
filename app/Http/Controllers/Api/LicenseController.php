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

            // 4. OBTENER CARACTERÍSTICAS (FEATURES) - BLOQUE BLINDADO
            $features = [];
            try {
                // INTENTO 1: Usando Query Builder (Nombres de tabla estándar)
                // Ajusta 'license_package' y 'feature_package' si tus tablas tienen prefijos o nombres distintos
                
                // Primero intentamos ver si existen las tablas para no generar error fatal
                // Nota: Esto es pseudo-verificación, idealmente deberías conocer tu esquema.
                
                $packageIds = DB::table('license_package')
                                ->where('license_id', $licenseData->id)
                                ->pluck('package_id');

                if ($packageIds->isNotEmpty()) {
                    $features = DB::table('features')
                                ->join('feature_package', 'features.id', '=', 'feature_package.feature_id')
                                ->whereIn('feature_package.package_id', $packageIds)
                                ->distinct()
                                ->pluck('features.slug') // Asegúrate que la columna se llame 'slug'
                                ->toArray();
                }

            } catch (\Exception $ex) {
                // Si falla la obtención de features (por nombres de tabla incorrectos), 
                // NO BLOQUEAMOS el sistema. Solo logueamos el error.
                Log::error("DIAGNOSTICO: Error obteniendo features (SQL): " . $ex->getMessage());
                
                // INTENTO DE FALLBACK: Si tienes una columna 'features' JSON directa en la tabla licenses (opcional)
                // $features = json_decode($licenseData->features ?? '[]', true);
            }

            // 5. RESPUESTA DE ÉXITO
            return response()->json([
                'status' => 'success',
                'valid' => true,
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => 'Standard',
                    'features' => $features // Enviamos lo que hayamos podido recuperar o array vacío
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