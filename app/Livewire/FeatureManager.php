<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads; // Importar Trait
use App\Models\Feature;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')] 
class FeatureManager extends Component
{
    use WithFileUploads; // Activar subida de archivos

    public $features;
    public $featureId;
    public $code, $label, $description, $icon, $category, $version;
    public $addonFile; // Variable para el archivo ZIP
    public $is_active = true;
    public $isModalOpen = false;

    public function mount()
    {
        $this->loadFeatures();
    }

    public function loadFeatures()
    {
        $this->features = Feature::orderBy('created_at', 'desc')->get();
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
        $this->featureId = null;
        $this->code = '';
        $this->label = '';
        $this->description = '';
        $this->icon = '📦';
        $this->category = 'general';
        $this->version = '1.0.0';
        $this->addonFile = null; // Limpiar archivo
        $this->is_active = true;
        $this->resetValidation();
    }

    public function editFeature($id)
    {
        $feature = Feature::findOrFail($id);
        $this->featureId = $feature->id;
        $this->code = $feature->code;
        $this->label = $feature->label;
        $this->description = $feature->description;
        $this->icon = $feature->icon;
        $this->category = $feature->category;
        $this->version = $feature->version ?? '1.0.0';
        $this->is_active = $feature->is_active;
        $this->addonFile = null;
        $this->isModalOpen = true;
    }

    public function saveFeature()
    {
        $this->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('features', 'code')->ignore($this->featureId)],
            'label' => 'required|string|max:100',
            'icon' => 'required|string|max:100',
            'category' => 'required|string',
            'is_active' => 'boolean',
            'addonFile' => 'nullable|file|mimes:zip|max:10240', // Max 10MB, solo ZIP
        ]);

        $data = [
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'category' => $this->category,
            'version' => $this->version,
            'is_active' => $this->is_active,
        ];

        // Guardar ZIP si se subió uno nuevo
        if ($this->addonFile) {
            $path = $this->addonFile->storeAs('addons', $this->code . '.zip'); // Guarda en storage/app/addons/codigo.zip
            $data['file_path'] = $path;
        }

        Feature::updateOrCreate(['id' => $this->featureId], $data);

        session()->flash('message', $this->featureId ? 'Módulo actualizado correctamente.' : 'Nuevo módulo creado.');
        $this->closeModal();
        $this->loadFeatures();
    }

    public function deleteFeature($id)
    {
        Feature::findOrFail($id)->delete();
        $this->loadFeatures();
        session()->flash('message', 'Módulo eliminado.');
    }

    public function render()
    {
        return view('livewire.feature-manager');
    }
}