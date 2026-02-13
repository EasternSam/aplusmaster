<div>
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Gestión de Clientes (SaaS)</h2>
            <p class="text-sm text-gray-500">Controla quién tiene acceso a tus distribuciones de SGA.</p>
        </div>
        <button wire:click="openModal" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow transition">
            + Nueva Licencia
        </button>
    </div>

    <!-- Alertas Flash -->
    @if (session()->has('message'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabla de Licencias -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider border-b border-gray-200">
                        <th class="p-4 font-semibold">Cliente</th>
                        <th class="p-4 font-semibold">Clave de Licencia</th>
                        <th class="p-4 font-semibold">Dominio Vinculado</th>
                        <th class="p-4 font-semibold">Expiración</th>
                        <th class="p-4 font-semibold text-center">Estado</th>
                        <th class="p-4 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse($licenses as $license)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 font-medium text-gray-800">{{ $license->client_name }}</td>
                            <td class="p-4 font-mono text-indigo-600 bg-indigo-50 rounded px-2 py-1 inline-block mt-2 ml-4 border border-indigo-100">
                                {{ $license->license_key }}
                            </td>
                            <td class="p-4 text-gray-500">
                                @if($license->registered_domain)
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                        {{ $license->registered_domain }}
                                    </span>
                                @else
                                    <span class="text-yellow-500 italic">Pendiente de instalación</span>
                                @endif
                            </td>
                            <td class="p-4">
                                {{ $license->expires_at ? $license->expires_at->format('d/m/Y') : 'De por vida' }}
                            </td>
                            <td class="p-4 text-center">
                                @if($license->is_active)
                                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-bold tracking-wide">ACTIVO</span>
                                @else
                                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-bold tracking-wide">SUSPENDIDO</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-2">
                                <!-- Botón Suspender/Activar -->
                                <button wire:click="toggleStatus({{ $license->id }})" class="text-sm px-3 py-1 rounded border {{ $license->is_active ? 'border-red-500 text-red-600 hover:bg-red-50' : 'border-green-500 text-green-600 hover:bg-green-50' }} transition">
                                    {{ $license->is_active ? 'Suspender' : 'Reactivar' }}
                                </button>
                                
                                <!-- Botón Desvincular Dominio -->
                                @if($license->registered_domain)
                                <button wire:click="revokeDomain({{ $license->id }})" title="Permitir instalar en otro dominio" class="text-sm px-3 py-1 rounded border border-gray-400 text-gray-600 hover:bg-gray-100 transition">
                                    Desvincular
                                </button>
                                @endif

                                <!-- Botón Eliminar -->
                                <button wire:click="deleteLicense({{ $license->id }})" wire:confirm="¿Estás seguro de eliminar esta licencia permanentemente? El cliente perderá el acceso inmediatamente." class="text-sm px-3 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 transition">
                                    Borrar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                No hay licencias generadas todavía. Haz clic en "Nueva Licencia" para empezar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nueva Licencia -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <!-- Fondo oscuro -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Contenido del modal -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="saveLicense">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Generar Nueva Licencia</h3>
                            <p class="text-sm text-gray-500 mt-1">Se creará una clave única que le darás a tu cliente para que instale el SGA.</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre del Cliente / Colegio</label>
                                <input type="text" wire:model="client_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ej. Instituto San Andrés" required>
                                @error('client_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha de Vencimiento (Opcional)</label>
                                <input type="date" wire:model="expires_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <p class="text-xs text-gray-400 mt-1">Déjalo en blanco si la licencia es de por vida.</p>
                                @error('expires_at') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Generar Clave
                        </button>
                        <button type="button" wire:click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
