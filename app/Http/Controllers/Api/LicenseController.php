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
                return response()->json(['status' => 'error', 'message' => 'Datos incompletos'], 400);
            }

            // Consulta directa a BD para evitar problemas de modelos/cache
            $licenseData = DB::table('licenses')->where('license_key', $key)->first();

            if (!$licenseData) {
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada'], 404);
            }

            // Validación de estado (Activa/Suspendida)
            $is_active = $licenseData->is_active ?? 0;
            
            if (!$is_active) {
                // Esta es la respuesta que SÍ funciona cuando suspendes
                return response()->json(['status' => 'error', 'message' => 'Licencia suspendida.'], 403);
            }

            // Normalización de dominios
            $incomingDomain = $this->cleanDomain($domain);
            $storedDomain = $this->cleanDomain($licenseData->registered_domain);

            if (empty($storedDomain)) {
                // Auto-registro
                DB::table('licenses')->where('id', $licenseData->id)->update(['registered_domain' => $incomingDomain]);
                Log::info("DIAGNOSTICO: Auto-registrado dominio {$incomingDomain}");
            } elseif ($incomingDomain !== $storedDomain) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "Dominio no autorizado ({$incomingDomain}). Registrado para: {$licenseData->registered_domain}"
                ], 403);
            }

            // RESPUESTA DE ÉXITO (Aquí es donde el cliente fallaba al interpretar)
            // Nos aseguramos de enviar 'status' => 'success' explícitamente
            Log::info("DIAGNOSTICO: Enviando respuesta de ÉXITO");
            
            return response()->json([
                'status' => 'success', // CLAVE CRÍTICA
                'valid' => true,       // Redundancia por si acaso
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => 'Standard', 
                    'features' => []
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error("DIAGNOSTICO: Error Fatal: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error interno del servidor.'], 500);
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