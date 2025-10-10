<?php

namespace App\Models\Digitra;

use App\Models\Digitra\Concerns\Cacheable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    use Cacheable;
    protected $connection = 'mysql'; // Conexión a Digitra (SOLO LECTURA)
    protected $table = 'users';

    // 🔒 SEGURIDAD: Bloquear mass-assignment
    protected $guarded = ['*'];

    // No permitir fillable en modelos de Digitra
    protected $fillable = [];

    protected $casts = [
        'permissions' => 'array',
        'is_colasistencia' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relación con establecimientos
     */
    public function establecimientos(): HasMany
    {
        return $this->hasMany(Establecimiento::class, 'user_id');
    }

    /**
     * Relación con reservas a través de establecimientos
     */
    public function reservas()
    {
        return $this->hasManyThrough(
            Reserva::class,
            Establecimiento::class,
            'user_id', // FK en establecimientos
            'establecimiento_id', // FK en reservas
            'id', // PK en users
            'id' // PK en establecimientos
        );
    }

    /**
     * Scope para filtrar solo usuarios activos (que tienen establecimientos)
     */
    public function scopeConEstablecimientos($query)
    {
        return $query->has('establecimientos');
    }
}
