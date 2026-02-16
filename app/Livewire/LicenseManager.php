<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\License;
use App\Models\Package;
use App\Models\Feature; // Importamos el modelo
use Illuminate\Support\Str;

class LicenseManager extends Component
{
    // ... (Propiedades existentes: $licenses, $packages, $licenseId, etc.) ...
    public $licenses;
    public $packages;
    public $licenseId = null;
    public $client_name;
    public $expires_at;
    public $package_id;
    public $activeFeatures = [];
    public $availableFeatures = [];
    public $isModalOpen = false;

    // ... (mount y loadData se mantienen igual) ...
    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->licenses = License::with('package')->orderBy('created_at', 'desc')->get();
        $this->packages = Package::all();
    }

    // ===> ESTA ES LA PARTE CLAVE QUE CAMBIA <===
    // En lugar de usar una constante, leemos de la tabla 'features'
    public function getFeatureCatalogProperty()
    {
        $features = Feature::where('is_active', true)->get();
        $catalog = [];

        foreach($features as $f) {
            $catalog[$f->code] = [
                'label' => $f->label,
                'icon'  => $f->icon,
                'desc'  => $f->description
            ];
        }

        return $catalog;
    }
    // ===========================================

    // ... (Resto de métodos: updatedPackageId, openModal, editLicense, etc. se mantienen igual
    // porque dependen de $this->featureCatalog, que ahora es dinámico gracias al método de arriba) ...
    
    public function updatedPackageId($value)
    {
        if ($value) {
            $package = $this->packages->find($value);
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
        $this->syncLists();
    }

    public function editLicense($id)
    {
        $this->resetForm();
        $license = License::findOrFail($id);

        $this->licenseId = $license->id;
        $this->client_name = $license->client_name;
        $this->expires_at = $license->expires_at ? $license->expires_at->format('Y-m-d') : null;
        $this->package_id = $license->package_id;

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

    public function syncLists()
    {
        // Usamos la propiedad computada dinámica
        $allKeys = array_keys($this->featureCatalog);
        $this->availableFeatures = array_values(array_diff($allKeys, $this->activeFeatures));
    }

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
        $this->availableFeatures = array_keys($this->featureCatalog);
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

        $isCustomized = true;
        if ($this->package_id) {
            $package = $this->packages->find($this->package_id);
            $planFeatures = $package->features ?? [];
            
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
            'custom_features' => $isCustomized ? $this->activeFeatures : null,
        ];

        if ($this->licenseId) {
            $license = License::find($this->licenseId);
            // $data['is_active'] = $license->is_active; // Mantener estado anterior
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
    
    public function revokeDomain($id)
    {
        $license = License::findOrFail($id);
        $license->registered_domain = null;
        $license->save();
        $this->loadData();
        session()->flash('message', 'Dominio desvinculado.');
    }

    public function deleteLicense($id)
    {
        License::findOrFail($id)->delete();
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.license-manager');
    }
}