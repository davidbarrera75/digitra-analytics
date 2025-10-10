<div class="flex items-center gap-3 px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg shadow-lg">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <label for="tenant-selector" class="text-sm font-semibold text-white">
            Ver como:
        </label>
    </div>

    <select
        wire:model.live="selectedTenantId"
        wire:change="switchTenant"
        id="tenant-selector"
        class="block w-64 rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
    >
        <option value="all">🌐 TODOS los Tenants (Sin Filtro)</option>
        @foreach($tenants as $tenant)
            <option value="{{ $tenant->id }}">
                👤 {{ $tenant->name }} ({{ $tenant->email }})
            </option>
        @endforeach
    </select>

    @if(session('active_tenant_id'))
        <button
            wire:click="$set('selectedTenantId', 'all')"
            wire:click="switchTenant"
            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-red-500 hover:bg-red-600 rounded-md transition-colors"
            title="Limpiar filtro"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
