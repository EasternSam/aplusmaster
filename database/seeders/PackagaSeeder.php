<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Package;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Definimos las "Capabilities" (Funciones) disponibles:
        // 'academic' -> Gestión académica básica
        // 'finance'  -> Módulo de pagos y facturación
        // 'inventory' -> Inventario de productos
        // 'virtual_classroom' -> Integración Moodle/Aula Virtual
        // 'reports_advanced' -> Reportes complejos/PDFs especiales
        // 'api_access' -> Acceso a la API para integraciones externas

        Package::firstOrCreate(['name' => 'Plan Básico (Start)'], [
            'features' => ['academic', 'reports_basic'],
        ]);

        Package::firstOrCreate(['name' => 'Plan Profesional (Pro)'], [
            'features' => ['academic', 'finance', 'reports_basic', 'reports_advanced'],
        ]);

        Package::firstOrCreate(['name' => 'Plan Enterprise (Full)'], [
            'features' => ['academic', 'finance', 'inventory', 'virtual_classroom', 'reports_basic', 'reports_advanced', 'api_access'],
        ]);
    }
}