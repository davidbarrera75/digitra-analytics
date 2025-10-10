<?php

namespace App\Models;

use App\Models\Digitra\Reserva;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservaCorregida extends Model
{
    protected $table = 'reservas_corregidas';

    protected $fillable = [
        'reserva_id',
        'precio_original',
        'precio_corregido',
        'motivo',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'reserva_id' => 'integer',
        'precio_original' => 'decimal:2',
        'precio_corregido' => 'decimal:2',
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
            'valor_atipico' => 'Valor Atípico Corregido',
            'error_digitacion' => 'Error de Digitación',
            'ajuste_manual' => 'Ajuste Manual',
            'otro' => 'Otro',
        ];

        return $motivos[$this->motivo] ?? 'Desconocido';
    }

    /**
     * Obtener la diferencia entre precio original y corregido
     */
    public function getDiferenciaAttribute(): float
    {
        return (float) ($this->precio_corregido - $this->precio_original);
    }
}
