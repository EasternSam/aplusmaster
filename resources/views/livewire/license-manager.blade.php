<div>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Estilos para el Drag & Drop -->
    <style>
        .draggable-source, .draggable-target {
            min-height: 200px;
            transition: all 0.2s;
        }
        .draggable-item {
            cursor: grab;
        }
        .draggable-item:active {
            cursor: grabbing;
        }
        .ghost {
            opacity: 0.5;
            background: #eef2ff;
            border: 2px dashed #6366f1;
        }
    </style>

    <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Panel Maestro SaaS</h2>
            <p class="text-sm text-gray-500">Administra distribuciones y módulos activos.</p>
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
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                        <th class="p-4 font-bold">Cliente</th>
                        <th class="p-4 font-bold">Plan & Licencia</th>
                        <th class="p-4 font-bold text-center">Estado</th>
                        <th class="p-4 font-bold text-right">Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($licenses as $license)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4">
                                <div class="font-bold text-gray-800">{{ $license->client_name }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ $license->registered_domain ?? 'Sin dominio' }}
                                </div>
                            </td>
                            <td class="p-4">
                                @if($license->custom_features)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200 uppercase tracking-wide">
                                        Personalizado
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200 uppercase tracking-wide">
                                        {{ $license->package->name ?? 'Sin Plan' }}
                                    </span>
                                @endif
                                <code class="text-xs font-mono text-gray-500 block mt-1 select-all">
                                    {{ $license->license_key }}
                                </code>
                            </td>
                            <td class="p-4 text-center">
                                <button wire:click="toggleStatus({{ $license->id }})" 
                                    class="relative inline-flex items-center h-5 rounded-full w-9 transition-colors duration-200 focus:outline-none {{ $license->is_active ? 'bg-green-500' : 'bg-gray-300' }}">
                                    <span class="translate-x-0.5 inline-block w-4 h-4 transform bg-white rounded-full transition-transform duration-200 {{ $license->is_active ? 'translate-x-4.5' : 'translate-x-0.5' }}"></span>
                                </button>
                            </td>
                            <td class="p-4 text-right">
                                <button wire:click="editLicense({{ $license->id }})" class="text-indigo-600 hover:text-indigo-900 font-medium">Configurar</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-400">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Drag & Drop -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                
                <!-- Encabezado Modal -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg leading-6 font-bold text-gray-900">{{ $licenseId ? 'Configurar Distribución' : 'Nueva Distribución' }}</h3>
                        <p class="text-sm text-gray-500">Arrastra los módulos para activarlos o desactivarlos.</p>
                    </div>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <span class="text-2xl">×</span>
                    </button>
                </div>

                <div class="px-6 py-6 space-y-6">
                    <!-- Configuración Básica -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Nombre Cliente</label>
                            <input type="text" wire:model="client_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Plan Base (Plantilla)</label>
                            <select wire:model.live="package_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                                <option value="">-- Personalizado --</option>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700">Vencimiento</label>
                            <input type="date" wire:model="expires_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                        </div>
                    </div>

                    <!-- AREA DE ARRASTRE -->
                    <!-- Alpine.js controla el drag & drop y sincroniza con Livewire al soltar -->
                    <div x-data="{
                        active: @entangle('activeFeatures'),
                        available: @entangle('availableFeatures'),
                        catalog: {{ json_encode($this->featureCatalog) }},
                        dragging: null,
                        
                        startDrag(event, item, origin) {
                            this.dragging = { item: item, origin: origin };
                            event.dataTransfer.effectAllowed = 'move';
                            event.dataTransfer.setData('text/plain', JSON.stringify(this.dragging));
                            event.target.classList.add('opacity-50');
                        },
                        
                        endDrag(event) {
                            event.target.classList.remove('opacity-50');
                            this.dragging = null;
                        },
                        
                        drop(event, targetList) {
                            const data = JSON.parse(event.dataTransfer.getData('text/plain'));
                            const itemKey = data.item;
                            const originList = data.origin;

                            if (originList === targetList) return;

                            // Mover item
                            if (targetList === 'active') {
                                this.available = this.available.filter(i => i !== itemKey);
                                if (!this.active.includes(itemKey)) this.active.push(itemKey);
                            } else {
                                this.active = this.active.filter(i => i !== itemKey);
                                if (!this.available.includes(itemKey)) this.available.push(itemKey);
                            }
                        }
                    }">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 h-[400px]">
                            
                            <!-- COLUMNA IZQUIERDA: DISPONIBLES (INACTIVOS) -->
                            <div class="flex flex-col h-full bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 p-4"
                                 @dragover.prevent
                                 @drop.prevent="drop($event, 'available')">
                                
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-gray-500 uppercase text-xs tracking-wider">Módulos Disponibles (Inactivos)</h4>
                                    <span class="bg-gray-200 text-gray-600 text-xs px-2 py-1 rounded-full" x-text="available.length"></span>
                                </div>

                                <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                                    <template x-for="key in available" :key="key">
                                        <div draggable="true"
                                             @dragstart="startDrag($event, key, 'available')"
                                             @dragend="endDrag($event)"
                                             class="draggable-item bg-white p-3 rounded-lg border border-gray-200 shadow-sm hover:shadow-md hover:border-gray-400 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-lg grayscale" x-text="catalog[key].icon"></div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-700" x-text="catalog[key].label"></p>
                                                <p class="text-xs text-gray-400" x-text="catalog[key].desc"></p>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="available.length === 0" class="text-center py-10 text-gray-400 text-sm italic">
                                        Todos los módulos están activos.
                                    </div>
                                </div>
                            </div>

                            <!-- COLUMNA DERECHA: ACTIVOS (EN LA DISTRIBUCIÓN) -->
                            <div class="flex flex-col h-full bg-indigo-50 rounded-xl border-2 border-indigo-200 p-4"
                                 @dragover.prevent
                                 @drop.prevent="drop($event, 'active')">
                                
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-bold text-indigo-600 uppercase text-xs tracking-wider">Módulos Activos (En Distribución)</h4>
                                    <span class="bg-indigo-200 text-indigo-700 text-xs px-2 py-1 rounded-full" x-text="active.length"></span>
                                </div>

                                <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                                    <template x-for="key in active" :key="key">
                                        <div draggable="true"
                                             @dragstart="startDrag($event, key, 'active')"
                                             @dragend="endDrag($event)"
                                             class="draggable-item bg-white p-3 rounded-lg border border-indigo-100 shadow-sm hover:shadow-md hover:border-indigo-400 flex items-center gap-3 ring-1 ring-indigo-50">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-lg" x-text="catalog[key].icon"></div>
                                            <div>
                                                <p class="text-sm font-bold text-indigo-900" x-text="catalog[key].label"></p>
                                                <p class="text-xs text-indigo-400" x-text="catalog[key].desc"></p>
                                            </div>
                                            <div class="ml-auto text-indigo-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                            </div>
                                        </div>
                                    </template>
                                    <div x-show="active.length === 0" class="text-center py-10 text-indigo-300 text-sm italic border-2 border-dashed border-indigo-200 rounded-lg">
                                        Arrastra módulos aquí para activarlos.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-200">
                    <button wire:click="saveLicense" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:text-sm">
                        Guardar Distribución
                    </button>
                    <button wire:click="closeModal" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>