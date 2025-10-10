<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Aplicar el global scope automáticamente
        static::addGlobalScope(new TenantScope());

        // Al crear un modelo, auto-asignar el tenant_id del usuario actual
        static::creating(function ($model) {
            if (auth()->check() && !isset($model->tenant_id)) {
                $user = auth()->user();

                // Si es super admin con tenant activo, usar ese
                if ($user->isSuperAdmin() && session()->has('active_tenant_id')) {
                    $model->tenant_id = session('active_tenant_id');
                }
                // Si no, usar el tenant del usuario
                elseif ($user->tenant_id) {
                    $model->tenant_id = $user->tenant_id;
                }
            }
        });
    }

    /**
     * Relación con el tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope para ignorar el filtro de tenant (solo para super admins)
     */
    public function scopeWithAllTenants($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope para filtrar por un tenant específico
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
