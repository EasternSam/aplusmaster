<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\License;
use Illuminate\Support\Str;

class LicenseManager extends Component
{
    public $licenses;
    
    // Variables del formulario modal
    public $client_name;
    public $expires_at;
    public $isModalOpen = false;

    public function mount()
    {
        $this->loadLicenses();
    }

    public function loadLicenses()
    {
        // Cargar todas las licencias ordenadas por la más reciente
        $this->licenses = License::orderBy('created_at', 'desc')->get();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->client_name = '';
        $this->expires_at = '';
    }

    // Generar una clave tipo APLUS-XXXX-XXXX-XXXX
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

        License::create([
            'client_name' => $this->client_name,
            'license_key' => $this->generateLicenseKey(),
            'is_active'   => true,
            'expires_at'  => $this->expires_at ? $this->expires_at : null,
        ]);

        $this->closeModal();
        $this->loadLicenses();
        
        session()->flash('message', 'Licencia generada exitosamente.');
    }

    public function toggleStatus($id)
    {
        $license = License::findOrFail($id);
        $license->is_active = !$license->is_active;
        $license->save();

        $this->loadLicenses();
        
        $estado = $license->is_active ? 'Activada' : 'Suspendida';
        session()->flash('message', "La licencia del cliente {$license->client_name} ha sido {$estado}.");
    }

    public function revokeDomain($id)
    {
        // Esto sirve por si el cliente cambia de servidor o se equivoca de dominio al instalar
        $license = License::findOrFail($id);
        $license->registered_domain = null;
        $license->save();

        $this->loadLicenses();
        session()->flash('message', 'Dominio desvinculado. El cliente podrá instalar el sistema en un nuevo dominio con esta misma clave.');
    }

    public function deleteLicense($id)
    {
        License::findOrFail($id)->delete();
        $this->loadLicenses();
        session()->flash('error', 'Licencia eliminada de forma permanente.');
    }

    public function render()
    {
        return view('livewire.license-manager');
    }
}
