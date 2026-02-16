<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Feature;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout; // Importar el atributo Layout

// Definir explícitamente el layout que debe usar este componente
#[Layout('layouts.app')] 
class FeatureManager extends Component
{
    public $features;
    public $featureId;
    public $code, $label, $description, $icon, $category;
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
        $this->is_active = $feature->is_active;
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
        ]);

        Feature::updateOrCreate(['id' => $this->featureId], [
            'code' => $this->code,
            'label' => $this->label,
            'description' => $this->description,
            'icon' => $this->icon,
            'category' => $this->category,
            'is_active' => $this->is_active,
        ]);

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