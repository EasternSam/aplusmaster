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

            // Consulta directa a BD para evitar problemas de modelos/cache
            $licenseData = DB::table('licenses')->where('license_key', $key)->first();

            if (!$licenseData) {
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada en Aplusmaster'], 404);
            }

            // Validación de estado (Activa/Suspendida)
            // Aseguramos conversión a booleano
            $is_active = (bool) ($licenseData->is_active ?? 0);
            
            if (!$is_active) {
                return response()->json(['status' => 'error', 'message' => 'Academic+: Su licencia ha sido SUSPENDIDA o expiró.'], 403);
            }

            // Normalización de dominios
            $incomingDomain = $this->cleanDomain($domain);
            $storedDomain = $this->cleanDomain($licenseData->registered_domain);

            // LOGICA DE DOMINIO
            if (empty($storedDomain)) {
                // Auto-registro: Si no hay dominio guardado, guardamos el entrante
                DB::table('licenses')->where('id', $licenseData->id)->update(['registered_domain' => $incomingDomain]);
                Log::info("DIAGNOSTICO: Auto-registrado dominio {$incomingDomain} para licencia {$key}");
            } 
            elseif ($incomingDomain !== $storedDomain) {
                // Bloqueo por dominio incorrecto
                $msg = "Dominio no autorizado. Esta licencia está registrada para: '{$storedDomain}', pero se está usando en: '{$incomingDomain}'.";
                Log::warning("DIAGNOSTICO: Bloqueo de dominio. Esperado: {$storedDomain}, Recibido: {$incomingDomain}");
                
                return response()->json([
                    'status' => 'error', 
                    'message' => $msg
                ], 403);
            }

            // RESPUESTA DE ÉXITO
            Log::info("DIAGNOSTICO: Licencia válida para {$incomingDomain}");
            
            return response()->json([
                'status' => 'success',
                'valid' => true,
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => 'Standard', 
                    'features' => []
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
        
        // Convertir a minúsculas
        $d = strtolower($url);
        
        // Quitar protocolo
        $d = preg_replace('#^https?://#', '', $d);
        
        // Quitar www.
        $d = preg_replace('#^www\.#', '', $d);
        
        // Quitar puertos (ej: localhost:8000 -> localhost) si se desea validación estricta de host
        // Por ahora lo dejamos para soportar puertos distintos en dev, pero quitamos paths
        $d = explode('/', $d)[0];
        
        // Quitar espacios
        return trim($d);
    }
}