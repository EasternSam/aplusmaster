<div>
    <!-- Inyectamos Tailwind CDN para asegurar que el diseño cargue correctamente sin compilación -->
    <script src="https://cdn.tailwindcss.com"></script>

    <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Panel Maestro SaaS</h2>
            <p class="text-sm text-gray-500">Administra distribuciones, planes y características.</p>
        </div>
        <button wire:click="openModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Nueva Distribución
        </button>
    </div>

    <!-- Alertas -->
    <div class="space-y-2 mb-6">
        @if (session()->has('message'))
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex justify-between">
                <span>{{ session('message') }}</span>
                <span class="cursor-pointer font-bold" onclick="this.parentElement.remove()">×</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex justify-between">
                <span>{{ session('error') }}</span>
                <span class="cursor-pointer font-bold" onclick="this.parentElement.remove()">×</span>
            </div>
        @endif
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                        <th class="p-4 font-bold">Cliente / Colegio</th>
                        <th class="p-4 font-bold">Plan & Licencia</th>
                        <th class="p-4 font-bold">Instalación</th>
                        <th class="p-4 font-bold text-center">Estado</th>
                        <th class="p-4 font-bold text-right">Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($licenses as $license)
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="p-4">
                                <div class="font-bold text-gray-800">{{ $license->client_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    Expira: {{ $license->expires_at ? $license->expires_at->format('d/m/Y') : 'Nunca (Vitalicio)' }}
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-700 border border-blue-200">
                                        {{ $license->package->name ?? 'Personalizado' }}
                                    </span>
                                </div>
                                <code class="text-xs font-mono text-gray-500 block mt-1 select-all bg-gray-100 px-1 py-0.5 rounded w-fit">
                                    {{ $license->license_key }}
                                </code>
                            </td>
                            <td class="p-4">
                                @if($license->registered_domain)
                                    <a href="https://{{ $license->registered_domain }}" target="_blank" class="text-indigo-600 hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        {{ $license->registered_domain }}
                                    </a>
                                @else
                                    <span class="text-gray-400 italic text-xs">Esperando instalación...</span>
                                @endif
                            </td>
                            <td class="p-4 text-center">
                                <button wire:click="toggleStatus({{ $license->id }})" 
                                    class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors duration-200 focus:outline-none {{ $license->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                                    <span class="sr-only">Toggle status</span>
                                    <span class="translate-x-1 inline-block w-4 h-4 transform bg-white rounded-full transition-transform duration-200 {{ $license->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                                <div class="text-[10px] uppercase font-bold mt-1 text-gray-500">
                                    {{ $license->is_active ? 'Activo' : 'Suspendido' }}
                                </div>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="editLicense({{ $license->id }})" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Editar Configuración">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    
                                    @if($license->registered_domain)
                                    <button wire:click="revokeDomain({{ $license->id }})" wire:confirm="¿Desvincular dominio? Esto permitirá reinstalar el sistema en otro servidor." class="p-2 text-gray-500 hover:text-orange-600 hover:bg-orange-50 rounded-lg transition" title="Resetear Dominio (Permitir reinstalación)">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                    @endif

                                    <button wire:click="deleteLicense({{ $license->id }})" wire:confirm="¿Eliminar definitivamente? El cliente perderá acceso total." class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar Licencia">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-400">
                                <svg class="w-16 h-16 mx-auto text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <p class="text-lg font-medium">No hay distribuciones activas</p>
                                <p class="text-sm">Crea la primera para comenzar.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Formulario -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity backdrop-blur-sm" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form wire:submit.prevent="saveLicense">
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-start mb-5">
                            <h3 class="text-xl leading-6 font-bold text-gray-900" id="modal-title">
                                {{ $licenseId ? 'Editar Distribución' : 'Nueva Distribución' }}
                            </h3>
                            <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Datos Generales -->
                            <div class="col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre del Cliente / Colegio</label>
                                <input type="text" wire:model="client_name" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej. Colegio San Agustín" required>
                                @error('client_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Plan Base</label>
                                <select wire:model.live="package_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Personalizado (Sin Plan) --</option>
                                    @foreach($packages as $pkg)
                                        <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">El plan define las funciones base.</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Vencimiento</label>
                                <input type="date" wire:model="expires_at" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Dejar vacío para licencia vitalicia.</p>
                            </div>

                            <!-- Selector de Funciones (Features) -->
                            <div class="col-span-2 border-t border-gray-100 pt-4 mt-2">
                                <label class="block text-sm font-bold text-gray-800 mb-3">Funciones y Módulos</label>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($this->getAvailableFeaturesConst() as $key => $label)
                                        @php
                                            // Verificar si la función está incluida en el plan base seleccionado
                                            $includedInPlan = in_array($key, $this->packageFeatures);
                                        @endphp
                                        
                                        <label class="flex items-center space-x-3 p-3 rounded-lg border transition cursor-pointer {{ $includedInPlan ? 'bg-indigo-50 border-indigo-200' : 'bg-white border-gray-200 hover:border-indigo-300' }}">
                                            <input type="checkbox" 
                                                   wire:model="custom_features" 
                                                   value="{{ $key }}"
                                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                                   {{ $includedInPlan ? 'checked disabled' : '' }}>
                                            
                                            <div class="flex-1">
                                                <span class="text-sm font-medium {{ $includedInPlan ? 'text-indigo-900' : 'text-gray-700' }}">
                                                    {{ $label }}
                                                </span>
                                                @if($includedInPlan)
                                                    <span class="text-[10px] text-indigo-500 block uppercase font-bold">Incluido en Plan</span>
                                                @endif
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-xs text-gray-400 mt-3">* Puedes seleccionar funciones extra adicionales a las que ya trae el plan base.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                        <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:text-sm">
                            {{ $licenseId ? 'Guardar Cambios' : 'Generar Licencia' }}
                        </button>
                        <button type="button" wire:click="closeModal" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>