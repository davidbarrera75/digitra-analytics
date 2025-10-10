<?php

namespace App\Models;

use App\Models\Digitra\User as DigitraUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'digitra_user_id',
        'email',
        'phone',
        'is_active',
        'settings',
        'trial_ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Boot method para auto-generar slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tenant) {
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);

                // Asegurar unicidad del slug
                $originalSlug = $tenant->slug;
                $count = 1;
                while (static::where('slug', $tenant->slug)->exists()) {
                    $tenant->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    /**
     * Relación con el usuario de Digitra (remoto)
     */
    public function digitraUser(): BelongsTo
    {
        return $this->belongsTo(DigitraUser::class, 'digitra_user_id');
    }

    /**
     * Relación con usuarios locales (admins) del tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope para tenants activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para tenants en trial
     */
    public function scopeInTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
                     ->where('trial_ends_at', '>=', now());
    }

    /**
     * Verificar si el tenant está en período de prueba
     */
    public function isInTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Verificar si el trial expiró
     */
    public function trialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    /**
     * Obtener días restantes de trial
     */
    public function trialDaysRemaining(): ?int
    {
        if (!$this->isInTrial()) {
            return null;
        }

        return now()->diffInDays($this->trial_ends_at);
    }

    /**
     * Obtener configuración específica
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Establecer configuración específica
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
        $this->save();
    }
}
