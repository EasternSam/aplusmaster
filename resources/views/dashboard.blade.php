<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Control Maestro') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- AQUÍ LLAMAMOS A NUESTRO COMPONENTE LIVEWIRE -->
            <livewire:license-manager />
        </div>
    </div>
</x-app-layout>