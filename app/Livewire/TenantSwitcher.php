<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Component;

class TenantSwitcher extends Component
{
    public $selectedTenantId;
    public $tenants = [];

    public function mount()
    {
        // Solo cargar si es super admin
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            return;
        }

        // Obtener todos los tenants ordenados por nombre
        $this->tenants = Tenant::orderBy('name')->get(['id', 'name', 'email']);

        // Obtener el tenant activo de la sesión
        $this->selectedTenantId = session('active_tenant_id', 'all');
    }

    public function switchTenant()
    {
        if (!auth()->user()->isSuperAdmin()) {
            session()->flash('error', 'No tienes permisos para cambiar de tenant.');
            return;
        }

        if ($this->selectedTenantId === 'all') {
            // Ver todos los datos (sin filtro)
            session()->forget('active_tenant_id');
            session()->flash('message', 'Viendo datos de TODOS los tenants.');
        } else {
            // Filtrar por tenant específico
            session(['active_tenant_id' => (int)$this->selectedTenantId]);
            $tenant = Tenant::find($this->selectedTenantId);
            session()->flash('message', "Viendo datos de: {$tenant->name}");
        }

        // Limpiar cache
        cache()->flush();

        // Recargar la página para aplicar filtros
        return redirect()->to(request()->header('Referer'));
    }

    public function render()
    {
        // Solo mostrar si es super admin
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            return view('livewire.tenant-switcher-empty');
        }

        return view('livewire.tenant-switcher');
    }
}
