<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'is_super_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Relación con el tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Verificar si el usuario es super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    /**
     * Verificar si el usuario puede ver todos los tenants
     */
    public function canViewAllTenants(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Obtener el tenant actual (para super admins puede ser diferente al tenant asignado)
     */
    /**
     * Determinar si el usuario puede acceder al panel de Filament
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Super admins siempre tienen acceso
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Usuarios normales necesitan tener un tenant activo
        if (!$this->tenant_id) {
            return false;
        }

        // Verificar que el tenant esté activo y no tenga trial expirado
        $tenant = $this->tenant;
        if (!$tenant || !$tenant->is_active) {
            return false;
        }

        // Si tiene trial, verificar que no haya expirado
        if ($tenant->trial_ends_at && $tenant->trialExpired()) {
            return false;
        }

        return true;
    }

    public function getCurrentTenant(): ?Tenant
    {
        // Si es super admin, puede tener un tenant "activo" en sesión
        if ($this->isSuperAdmin() && session()->has('active_tenant_id')) {
            return Tenant::find(session('active_tenant_id'));
        }

        return $this->tenant;
    }
}
