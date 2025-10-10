<?php

use App\Helpers\TenantHelper;
use App\Models\Tenant;

if (!function_exists('tenant')) {
    /**
     * Obtener el tenant actual
     */
    function tenant(): ?Tenant
    {
        return TenantHelper::current();
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Obtener el ID del tenant actual
     */
    function tenant_id(): ?int
    {
        return TenantHelper::currentId();
    }
}

if (!function_exists('digitra_user_id')) {
    /**
     * Obtener el ID del usuario de Digitra del tenant actual
     */
    function digitra_user_id(): ?int
    {
        return TenantHelper::getDigitraUserId();
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Verificar si el usuario actual es super admin
     */
    function is_super_admin(): bool
    {
        return TenantHelper::isSuperAdmin();
    }
}
