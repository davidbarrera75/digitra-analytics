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

        // Guardar tenant anterior para limpiar su cache
        $oldTenantId = session('active_tenant_id', 'all');

        if ($this->selectedTenantId === 'all') {
            session()->forget('active_tenant_id');
            session()->flash('message', 'Viendo datos de TODOS los tenants.');
            $newTenantId = 'all';
        } else {
            session(['active_tenant_id' => (int)$this->selectedTenantId]);
            $tenant = Tenant::find($this->selectedTenantId);
            session()->flash('message', "Viendo datos de: {$tenant->name}");
            $newTenantId = (int)$this->selectedTenantId;
        }

        // Limpiar solo cache relacionada con los tenants involucrados (NO flush global)
        $keysToForget = ['digitra_top_propiedades_ids'];
        foreach ([$oldTenantId, $newTenantId, 'all'] as $tid) {
            $keysToForget[] = 'digitra_stats_overview_' . $tid;
            $keysToForget[] = 'digitra_reservas_por_mes_chart_' . $tid;
        }
        foreach ($keysToForget as $key) {
            cache()->forget($key);
        }

        // Recargar la página para aplicar filtros (usando ruta segura)
        return redirect()->to(url()->previous('/admin'));
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
