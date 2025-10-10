<?php

namespace App\Helpers;

use App\Models\Tenant;

class TenantHelper
{
    /**
     * Obtener el tenant actual basado en el usuario autenticado
     */
    public static function current(): ?Tenant
    {
        if (!auth()->check()) {
            return null;
        }

        $user = auth()->user();

        // Si es super admin con tenant activo en sesión
        if ($user->isSuperAdmin() && session()->has('active_tenant_id')) {
            return Tenant::find(session('active_tenant_id'));
        }

        // Usuario normal: retornar su tenant
        return $user->tenant;
    }

    /**
     * Obtener el ID del tenant actual
     */
    public static function currentId(): ?int
    {
        $tenant = self::current();
        return $tenant?->id;
    }

    /**
     * Establecer el tenant activo para super admins
     */
    public static function setActive(?int $tenantId): void
    {
        if (!auth()->check() || !auth()->user()->isSuperAdmin()) {
            throw new \Exception('Solo super administradores pueden cambiar de tenant');
        }

        if ($tenantId) {
            session(['active_tenant_id' => $tenantId]);
        } else {
            session()->forget('active_tenant_id');
        }
    }

    /**
     * Verificar si el usuario actual es super admin
     */
    public static function isSuperAdmin(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    /**
     * Obtener el usuario de Digitra asociado al tenant actual
     */
    public static function getDigitraUser()
    {
        $tenant = self::current();
        return $tenant?->digitraUser;
    }

    /**
     * Obtener el ID del usuario de Digitra asociado al tenant actual
     */
    public static function getDigitraUserId(): ?int
    {
        $tenant = self::current();
        return $tenant?->digitra_user_id;
    }
}
