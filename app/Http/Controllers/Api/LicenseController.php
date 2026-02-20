<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\License;

class LicenseController extends Controller
{
    public function validateLicense(Request $request)
    {
        try {
            $key = $request->input('license_key');
            $domain = $request->input('domain');

            if (!$key || !$domain) {
                return response()->json(['status' => 'error', 'message' => 'Datos incompletos'], 400);
            }

            $licenseData = DB::table('licenses')->where('license_key', $key)->first();

            if (!$licenseData) {
                return response()->json(['status' => 'error', 'message' => 'Licencia no encontrada'], 404);
            }

            $is_active = (bool) ($licenseData->is_active ?? 0);
            if (!$is_active) {
                return response()->json(['status' => 'error', 'message' => 'Academic+: Su licencia ha sido SUSPENDIDA o expiró.'], 403);
            }

            $incomingDomain = $this->cleanDomain($domain);
            $storedDomain = $this->cleanDomain($licenseData->registered_domain);

            if (empty($storedDomain)) {
                DB::table('licenses')->where('id', $licenseData->id)->update(['registered_domain' => $incomingDomain]);
            } 
            elseif ($incomingDomain !== $storedDomain) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "Dominio no autorizado ({$incomingDomain}). Registrado para: {$storedDomain}"
                ], 403);
            }

            $features = [];
            
            try {
                if (!empty($licenseData->package_id)) {
                    $package = DB::table('packages')->where('id', $licenseData->package_id)->first();
                    
                    if ($package && !empty($package->features)) {
                        $packageFeatures = json_decode($package->features, true);
                        if (is_array($packageFeatures)) {
                            $features = array_merge($features, $packageFeatures);
                        }
                    }
                }

                if (!empty($licenseData->custom_features)) {
                    $customFeatures = json_decode($licenseData->custom_features, true);
                    if (is_array($customFeatures)) {
                        $features = array_merge($features, $customFeatures);
                    }
                }

                $features = array_values(array_unique($features));

            } catch (\Exception $ex) {
                Log::error("DIAGNOSTICO: Error procesando features JSON: " . $ex->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'valid' => true,
                'message' => 'Licencia válida.',
                'data' => [
                    'plan_name' => 'Standard',
                    'features' => $features,
                    'academic_mode' => $licenseData->academic_mode ?? 'both' // Enviamos el modo académico
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