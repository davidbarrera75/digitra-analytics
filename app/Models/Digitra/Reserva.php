<?php

namespace App\Models\Digitra;

use App\Models\Digitra\Concerns\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    use Cacheable;
    protected $connection = 'mysql'; // SOLO LECTURA
    protected $table = 'reservas';

    // 🔒 SEGURIDAD: Bloquear mass-assignment
    protected $guarded = ['*'];
    protected $fillable = [];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'tra_send' => 'boolean',
        'tra_send_at' => 'datetime',
        'is_active' => 'boolean',
        'seguro' => 'boolean',
        'fecha_seguro' => 'datetime',
        'precio' => 'decimal:2',
    ];

    /**
     * Relación con establecimiento
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class);
    }

    /**
     * Relación con huéspedes
     */
    public function huespedes(): HasMany
    {
        return $this->hasMany(Huesped::class, 'reserva_id');
    }

    /**
     * Scope para reservas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para reservas en un rango de fechas
     */
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('check_in', [$desde, $hasta]);
    }

    /**
     * Scope para reservas futuras
     */
    public function scopeFuturas($query)
    {
        return $query->where('check_in', '>=', now());
    }

    /**
     * Scope para reservas pasadas
     */
    public function scopePasadas($query)
    {
        return $query->where('check_out', '<', now());
    }

    /**
     * Scope para reservas en curso
     */
    public function scopeEnCurso($query)
    {
        return $query->where('check_in', '<=', now())
                     ->where('check_out', '>=', now());
    }

    /**
     * Accessor para calcular duración
     */
    public function getDuracionAttribute()
    {
        if (!$this->check_in || !$this->check_out) {
            return 0;
        }
        return $this->check_in->diffInDays($this->check_out);
    }

    /**
     * Accessor para el estado de la reserva
     */
    public function getEstadoAttribute()
    {
        if ($this->check_out < now()) {
            return 'Finalizada';
        }
        if ($this->check_in > now()) {
            return 'Futura';
        }
        return 'En curso';
    }
}
