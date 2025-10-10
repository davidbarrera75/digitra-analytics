<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Solo aplicar si hay un usuario autenticado y no es super admin
        if (auth()->check()) {
            $user = auth()->user();

            // Super admins ven todo
            if ($user->isSuperAdmin()) {
                // Si hay un tenant activo en sesión, filtrar por ese tenant
                if (session()->has('active_tenant_id')) {
                    $builder->where($model->getTable() . '.tenant_id', session('active_tenant_id'));
                }
                // Si no hay tenant activo, no filtrar (ve todo)
                return;
            }

            // Usuarios normales solo ven datos de su tenant
            if ($user->tenant_id) {
                $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
            }
        }
    }
}
