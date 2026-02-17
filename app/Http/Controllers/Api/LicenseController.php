<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // Usaremos DB directo para descartar problemas de Eloquent

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        // 1. Log inicial
        Log::info("DIAGNOSTICO: Inicio validación", $request->all());

        try {
            $key = $request->input('license_key');
            $domain = $request->input('domain');

            if (!$key || !$domain) {
                Log::warning("DIAGNOSTICO: Faltan datos");
                return response()->json(['status' => 'error', 'message' => 'Datos incompletos'], 400);
            }

            // 2. Prueba de conexión a BD pura
            try {
                // Intentamos una consulta cruda para ver si la BD responde
                $licenseData = DB::table('licenses')->where('license_key', $key)->first();
            } catch (\Exception $e) {
                Log::error("DIAGNOSTICO: Error Conexión BD: " . $e->getMessage());
                return response()->json(['status' => 'error', 'message' => 'Error BD Server'], 500);
            }

            // 3. Resultado de la consulta
            if (!$licenseData) {
                Log::info("DIAGNOSTICO: Licencia no encontrada en tabla 'licenses'");
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada'], 404);
            }

            Log::info("DIAGNOSTICO: Licencia encontrada ID: " . $licenseData->id);

            // 4. Lógica de validación manual (sin modelos)
            $is_active = $licenseData->is_active ?? 0;
            $expires_at = $licenseData->expires_at;
            $registered_domain = $licenseData->registered_domain;

            if (!$is_active) {
                return response()->json(['status' => 'error', 'message' => 'Suspendida'], 403);
            }

            // Normalizar dominios
            $incomingDomain = $this->cleanDomain($domain);
            $storedDomain = $this->cleanDomain($registered_domain);

            Log::info("DIAGNOSTICO: Dominios", ['recibido' => $incomingDomain, 'guardado' => $storedDomain]);

            if (empty($storedDomain)) {
                // Auto-registro
                DB::table('licenses')->where('id', $licenseData->id)->update(['registered_domain' => $incomingDomain]);
                Log::info("DIAGNOSTICO: Auto-registrado");
            } elseif ($incomingDomain !== $storedDomain) {
                Log::warning("DIAGNOSTICO: Mismatch de dominio");
                return response()->json(['status' => 'error', 'message' => 'Dominio incorrecto'], 403);
            }

            Log::info("DIAGNOSTICO: Éxito total");
            return response()->json([
                'status' => 'success',
                'message' => 'Validada',
                'data' => ['plan_name' => 'Standard', 'features' => []]
            ], 200);

        } catch (\Throwable $e) {
            Log::error("DIAGNOSTICO: Crash Fatal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error Fatal'], 500);
        }
    }

    private function cleanDomain($url)
    {
        if (empty($url)) return null;
        $d = preg_replace('#^https?://#', '', $url);
        $d = preg_replace('#^www\.#', '', $d);
        return rtrim($d, '/');
    }
}