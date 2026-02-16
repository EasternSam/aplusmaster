<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        // Tu catálogo actual migrado a DB
        $catalog = [
            'academic' => ['label' => 'Gestión Académica', 'icon' => '🎓', 'desc' => 'Notas, Estudiantes, Cursos'],
            'finance' => ['label' => 'Módulo Financiero', 'icon' => '💰', 'desc' => 'Pagos, Caja, Reportes'],
            'inventory' => ['label' => 'Inventario', 'icon' => '📦', 'desc' => 'Productos, Stock, Ventas'],
            'virtual_classroom' => ['label' => 'Aula Virtual', 'icon' => '💻', 'desc' => 'Integración Moodle/LMS'],
            'reports_basic' => ['label' => 'Reportes Básicos', 'icon' => '📄', 'desc' => 'Listados PDF sencillos'],
            'reports_advanced' => ['label' => 'Reportes Avanzados', 'icon' => '📊', 'desc' => 'Estadísticas, Diplomas, BI'],
            'api_access' => ['label' => 'Acceso API', 'icon' => '🔌', 'desc' => 'Conexiones externas'],
        ];

        foreach ($catalog as $code => $data) {
            Feature::updateOrCreate(['code' => $code], [
                'label' => $data['label'],
                'icon' => $data['icon'],
                'description' => $data['desc'],
                'is_active' => true,
            ]);
        }
    }
}