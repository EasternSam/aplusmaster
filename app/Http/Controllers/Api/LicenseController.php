<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        Log::info("DIAGNOSTICO: Inicio validación", $request->all());

        try {
            $key = $request->input('license_key');
            $domain = $request->input('domain');

            if (!$key || !$domain) {
                return response()->json(['status' => 'error', 'message' => 'Datos incompletos (Key o Dominio faltante)'], 400);
            }

            // 1. Obtener la licencia
            $licenseData = DB::table('licenses')->where('license_key', $key)->first();

            if (!$licenseData) {
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada en Aplusmaster'], 404);
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
                // Auto-registro
                DB::table('licenses')->where('id', $licenseData->id)->update(['registered_domain' => $incomingDomain]);
                Log::info("DIAGNOSTICO: Auto-registrado dominio {$incomingDomain} para licencia {$key}");
            } 
            elseif ($incomingDomain !== $storedDomain) {
                $msg = "Dominio no autorizado. Esta licencia está registrada para: '{$storedDomain}', pero se está usando en: '{$incomingDomain}'.";
                Log::warning("DIAGNOSTICO: Bloqueo de dominio. Esperado: {$storedDomain}, Recibido: {$incomingDomain}");
                
                return response()->json([
                    'status' => 'error', 
                    'message' => $msg
                ], 403);
            }

            // 4. OBTENER CARACTERÍSTICAS (FEATURES)
            // Lógica: Licencia -> Paquetes -> Features
            // Asumimos tablas pivot: license_package y feature_package (o similar según tu estructura en aplusmaster)
            // Si no tienes modelos pivot, usamos query builder directo.
            
            // Paso A: Obtener IDs de paquetes de la licencia
            $packageIds = DB::table('license_package')
                            ->where('license_id', $licenseData->id)
                            ->pluck('package_id');

            // Paso B: Obtener Features de esos paquetes
            // Asumiendo tabla 'feature_package' con 'feature_id' y 'package_id'
            // Y tabla 'features' con 'slug' o 'code'
            $features = DB::table('features')
                        ->join('feature_package', 'features.id', '=', 'feature_package.feature_id')
                        ->whereIn('feature_package.package_id', $packageIds)
                        ->distinct()
                        ->pluck('features.slug') // O 'code', o 'name' según tu columna clave
                        ->toArray();

            // RESPUESTA DE ÉXITO
            Log::info("DIAGNOSTICO: Licencia válida para {$incomingDomain}. Features: " . json_encode($features));
            
            return response()->json([
                'status' => 'success',
                'valid' => true,
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => 'Standard', // Podrías hacerlo dinámico también si quieres
                    'features' => $features // ARRAY DE CARACTERÍSTICAS REAL
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error("DIAGNOSTICO: Error Fatal en LicenseController: " . $e->getMessage());
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