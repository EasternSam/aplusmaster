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
    public $licenseId = null; // Si es null, estamos creando. Si tiene ID, editamos.
    public $client_name;
    public $expires_at;
    public $package_id;
    public $custom_features = []; // Array para los checkboxes
    public $isModalOpen = false;

    // Lista maestra de funciones disponibles en tu sistema
    // Debe coincidir con las que usas en el Seeder y en el Helper SaaS::has()
    const AVAILABLE_FEATURES = [
        'academic' => 'Gestión Académica',
        'finance' => 'Módulo Financiero',
        'inventory' => 'Inventario',
        'virtual_classroom' => 'Aula Virtual (Moodle)',
        'reports_basic' => 'Reportes Básicos',
        'reports_advanced' => 'Reportes Avanzados',
        'api_access' => 'Acceso API',
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

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function editLicense($id)
    {
        $this->resetForm();
        $license = License::findOrFail($id);

        $this->licenseId = $license->id;
        $this->client_name = $license->client_name;
        $this->expires_at = $license->expires_at ? $license->expires_at->format('Y-m-d') : null;
        $this->package_id = $license->package_id;
        $this->custom_features = $license->custom_features ?? [];

        $this->isModalOpen = true;
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
        $this->custom_features = [];
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
            'package_id'  => 'nullable|exists:packages,id',
            'expires_at'  => 'nullable|date',
            'custom_features' => 'array'
        ]);

        // Datos comunes
        $data = [
            'client_name' => $this->client_name,
            'package_id'  => $this->package_id ?: null,
            'is_active'   => true, // Por defecto activo al crear/editar
            'expires_at'  => $this->expires_at ? $this->expires_at : null,
            // Filtramos el array para guardar solo las keys activas (ej: ['finance', 'api'])
            'custom_features' => count($this->custom_features) > 0 ? array_values($this->custom_features) : null,
        ];

        if ($this->licenseId) {
            // Actualizar
            $license = License::find($this->licenseId);
            // Mantenemos el estado activo/inactivo original al editar, no lo forzamos a true
            $data['is_active'] = $license->is_active; 
            $license->update($data);
            session()->flash('message', 'Cliente actualizado correctamente.');
        } else {
            // Crear Nuevo
            $data['license_key'] = $this->generateLicenseKey();
            License::create($data);
            session()->flash('message', 'Nueva licencia generada exitosamente.');
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
        
        $estado = $license->is_active ? 'Reactivada' : 'Suspendida';
        session()->flash('message', "La licencia ha sido {$estado}.");
    }

    public function revokeDomain($id)
    {
        $license = License::findOrFail($id);
        $license->registered_domain = null;
        $license->save();
        $this->loadData();
        session()->flash('message', 'Dominio desvinculado. Listo para re-instalación.');
    }

    public function deleteLicense($id)
    {
        License::findOrFail($id)->delete();
        $this->loadData();
        session()->flash('error', 'Licencia eliminada permanentemente.');
    }

    // Propiedad computada para la vista: Obtener features del paquete seleccionado
    public function getPackageFeaturesProperty()
    {
        if (!$this->package_id) return [];
        $package = $this->packages->find($this->package_id);
        return $package ? ($package->features ?? []) : [];
    }

    // Helper para la vista
    public function getAvailableFeaturesConst()
    {
        return self::AVAILABLE_FEATURES;
    }

    public function render()
    {
        return view('livewire.license-manager');
    }
}