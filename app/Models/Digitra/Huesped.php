<?php

namespace App\Models\Digitra;

use App\Models\Digitra\Concerns\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Huesped extends Model
{
    use Cacheable;
    protected $connection = 'mysql'; // SOLO LECTURA
    protected $table = 'huespedes';

    // 🔒 SEGURIDAD: Bloquear mass-assignment
    protected $guarded = ['*'];
    protected $fillable = [];

    protected $casts = [
        'principal' => 'boolean',
        'asegurado' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Relación con reserva
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    /**
     * Scope para huéspedes principales
     */
    public function scopePrincipales($query)
    {
        return $query->where('principal', true);
    }

    /**
     * Accessor para nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    /**
     * Accessor para edad
     */
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        return $this->fecha_nacimiento->age;
    }
}
