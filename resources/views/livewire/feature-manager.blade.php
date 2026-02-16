<div class="p-6">
    <script src="https://cdn.tailwindcss.com"></script>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestión de Addons (Repositorio)</h2>
            <p class="text-sm text-gray-500">Sube los paquetes .ZIP para distribución automática.</p>
        </div>
        <button wire:click="openModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nuevo Addon
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($features as $feature)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition relative group">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-xl">
                            {{ $feature->icon }}
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $feature->label }}</h3>
                            <div class="flex items-center gap-2">
                                <code class="text-xs bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded border border-gray-200">{{ $feature->code }}</code>
                                @if($feature->file_path)
                                    <span class="text-[10px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200">ZIP Listo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <button wire:click="editFeature({{ $feature->id }})" class="p-1 text-gray-400 hover:text-indigo-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button wire:click="deleteFeature({{ $feature->id }})" wire:confirm="¿Seguro que deseas eliminar este addon?" class="p-1 text-gray-400 hover:text-red-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
                
                <p class="text-sm text-gray-600 mb-3 line-clamp-2 h-10">{{ $feature->description }}</p>
                
                <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-100">
                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $feature->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $feature->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                    <span class="text-xs text-gray-500">v{{ $feature->version ?? '1.0' }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Modal Form -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="saveFeature">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $featureId ? 'Editar Addon' : 'Nuevo Addon' }}</h3>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre del Módulo</label>
                                <input type="text" wire:model="label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('label') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Código (Slug)</label>
                                    <input type="text" wire:model="code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50 font-mono text-sm">
                                    @error('code') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Icono</label>
                                    <input type="text" wire:model="icon" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-center">
                                    @error('icon') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Categoría</label>
                                    <select wire:model="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="general">General</option>
                                        <option value="academic">Académico</option>
                                        <option value="finance">Finanzas</option>
                                        <option value="hr">Recursos Humanos</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Versión</label>
                                    <input type="text" wire:model="version" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="1.0.0">
                                </div>
                            </div>

                            <!-- FILE UPLOAD ZONE -->
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Archivo del Módulo (.zip)</label>
                                <input type="file" wire:model="addonFile" accept=".zip" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <div wire:loading wire:target="addonFile" class="text-xs text-indigo-600 mt-1">Subiendo...</div>
                                @if($featureId)
                                    <p class="text-xs text-gray-400 mt-1">Deja vacío para mantener el archivo actual.</p>
                                @endif
                                @error('addonFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Descripción</label>
                                <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900">Activo</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>