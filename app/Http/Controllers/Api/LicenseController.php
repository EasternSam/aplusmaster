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
        $request->validate([
            'license_key' => 'required|string',
            'domain'      => 'required|string',
        ]);

        // Normalizar dominio entrante (quitar www. y http/s)
        $incomingDomain = $this->normalizeDomain($request->domain);

        Log::info("MASTER LICENCIA: Validando...", [
            'key' => $request->license_key,
            'dominio_recibido' => $incomingDomain,
            'dominio_original' => $request->domain
        ]);

        $license = License::where('license_key', $request->license_key)
                          ->with('package')
                          ->first();

        if (!$license) {
            return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada.'], 404);
        }

        if (!$license->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Licencia suspendida.'], 403);
        }
        
        if ($license->expires_at && now()->greaterThan($license->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'Licencia expirada.'], 403);
        }

        // Normalizar dominio registrado en BD
        $registeredDomain = $this->normalizeDomain($license->registered_domain);

        // LÓGICA DE DOMINIO
        if (empty($registeredDomain)) {
            // Auto-registro
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
                'message' => "Dominio no autorizado ({$incomingDomain}). Registrado para: {$license->registered_domain}"
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
    }

    private function normalizeDomain($url)
    {
        if (empty($url)) return null;
        
        // Quitar protocolo
        $domain = preg_replace('#^https?://#', '', $url);
        // Quitar www.
        $domain = preg_replace('#^www\.#', '', $domain);
        // Quitar slashes al final
        return rtrim($domain, '/');
    }
}