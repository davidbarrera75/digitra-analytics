<?php

namespace App\Models;

use App\Models\Digitra\Reserva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaIgnorada extends Model
{
    protected $table = 'reservas_ignoradas';

    protected $fillable = [
        'reserva_id',
        'motivo',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'reserva_id' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Relación con la reserva de Digitra (MySQL)
     */
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    /**
     * Obtener el motivo en formato legible
     */
    public function getMotivoLegibleAttribute(): string
    {
        $motivos = [
            'duplicada' => 'Reserva Duplicada',
            'error_datos' => 'Error en Datos',
            'test' => 'Reserva de Prueba',
            'otro' => 'Otro',
        ];

        return $motivos[$this->motivo] ?? 'Desconocido';
    }
}
