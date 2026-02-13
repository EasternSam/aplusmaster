<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\License;
use App\Models\Package;
use Illuminate\Support\Str;

class LicenseManager extends Component
{
    public $licenses;
    public $packages;
    
    // Estado del Formulario
    public $licenseId = null;
    public $client_name;
    public $expires_at;
    public $package_id;
    
    // Listas para Drag & Drop
    public $activeFeatures = []; // Módulos activados (Derecha)
    public $availableFeatures = []; // Módulos disponibles (Izquierda)
    
    public $isModalOpen = false;

    // Catálogo Maestro de Módulos (Definición visual)
    const FEATURE_CATALOG = [
        'academic' => ['label' => 'Gestión Académica', 'icon' => '🎓', 'desc' => 'Notas, Estudiantes, Cursos'],
        'finance' => ['label' => 'Módulo Financiero', 'icon' => '💰', 'desc' => 'Pagos, Caja, Reportes de Ingresos'],
        'inventory' => ['label' => 'Inventario', 'icon' => '📦', 'desc' => 'Productos, Stock, Ventas'],
        'virtual_classroom' => ['label' => 'Aula Virtual', 'icon' => '💻', 'desc' => 'Integración Moodle/LMS'],
        'reports_basic' => ['label' => 'Reportes Básicos', 'icon' => 'impr', 'desc' => 'Listados PDF sencillos'],
        'reports_advanced' => ['label' => 'Reportes Avanzados', 'icon' => '📊', 'desc' => 'Estadísticas, BI, Exportación'],
        'api_access' => ['label' => 'Acceso API', 'icon' => '🔌', 'desc' => 'Conexiones externas'],
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->licenses = License::with('package')->orderBy('created_at', 'desc')->get();
        $this->packages = Package::all();
    }

    // Cuando cambia el plan seleccionado, rellenamos la lista activa automáticamente
    public function updatedPackageId($value)
    {
        if ($value) {
            $package = $this->packages->find($value);
            // Convertimos las features del plan en la lista activa
            $this->activeFeatures = $package->features ?? [];
        } else {
            $this->activeFeatures = [];
        }
        $this->syncLists();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
        $this->syncLists(); // Inicializar listas
    }

    public function editLicense($id)
    {
        $this->resetForm();
        $license = License::findOrFail($id);

        $this->licenseId = $license->id;
        $this->client_name = $license->client_name;
        $this->expires_at = $license->expires_at ? $license->expires_at->format('Y-m-d') : null;
        $this->package_id = $license->package_id;

        // Si tiene personalización, esa es la verdad. Si no, tomamos del paquete.
        if (!is_null($license->custom_features)) {
            $this->activeFeatures = $license->custom_features;
        } elseif ($license->package) {
            $this->activeFeatures = $license->package->features ?? [];
        } else {
            $this->activeFeatures = [];
        }

        $this->syncLists();
        $this->isModalOpen = true;
    }

    // Calcula qué queda disponible basándose en lo que ya está activo
    public function syncLists()
    {
        $allKeys = array_keys(self::FEATURE_CATALOG);
        // Disponibles = Todos - Activos
        $this->availableFeatures = array_values(array_diff($allKeys, $this->activeFeatures));
    }

    // Método llamado desde JS cuando se suelta un item
    public function updateFeatureLists($active, $available)
    {
        $this->activeFeatures = $active;
        $this->availableFeatures = $available;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->licenseId = null;
        $this->client_name = '';
        $this->expires_at = '';
        $this->package_id = null;
        $this->activeFeatures = [];
        $this->availableFeatures = array_keys(self::FEATURE_CATALOG);
        $this->resetValidation();
    }

    private function generateLicenseKey()
    {
        return 'APLUS-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
    }

    public function saveLicense()
    {
        $this->validate([
            'client_name' => 'required|string|max:255',
            'expires_at'  => 'nullable|date',
        ]);

        // Detectar si la lista actual difiere del plan original
        $isCustomized = true;
        if ($this->package_id) {
            $package = $this->packages->find($this->package_id);
            $planFeatures = $package->features ?? [];
            
            // Si tienen los mismos elementos (sin importar orden), no es custom
            sort($planFeatures);
            $currentActive = $this->activeFeatures;
            sort($currentActive);
            
            if ($planFeatures == $currentActive) {
                $isCustomized = false;
            }
        }

        $data = [
            'client_name' => $this->client_name,
            'package_id'  => $this->package_id ?: null,
            'is_active'   => true,
            'expires_at'  => $this->expires_at ? $this->expires_at : null,
            // Si se personalizó (arrastró), guardamos la lista exacta. Si no, NULL (hereda del plan).
            'custom_features' => $isCustomized ? $this->activeFeatures : null,
        ];

        if ($this->licenseId) {
            $license = License::find($this->licenseId);
            $data['is_active'] = $license->is_active; 
            $license->update($data);
            session()->flash('message', 'Distribución actualizada.');
        } else {
            $data['license_key'] = $this->generateLicenseKey();
            License::create($data);
            session()->flash('message', 'Distribución creada.');
        }

        $this->closeModal();
        $this->loadData();
    }

    public function toggleStatus($id)
    {
        $license = License::findOrFail($id);
        $license->is_active = !$license->is_active;
        $license->save();
        $this->loadData();
    }

    public function deleteLicense($id)
    {
        License::findOrFail($id)->delete();
        $this->loadData();
    }

    public function getFeatureCatalogProperty()
    {
        return self::FEATURE_CATALOG;
    }

    public function render()
    {
        return view('livewire.license-manager');
    }
}