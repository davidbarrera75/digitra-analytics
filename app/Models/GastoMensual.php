<?php

namespace App\Models;

use App\Models\Digitra\Establecimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoMensual extends Model
{
    protected $table = 'gastos_mensuales';

    protected $fillable = [
        'establecimiento_id',
        'mes',
        'año',
        'aseo',
        'administracion',
        'otros_gastos',
        'notas',
    ];

    protected $casts = [
        'aseo' => 'decimal:2',
        'administracion' => 'decimal:2',
        'otros_gastos' => 'decimal:2',
        'mes' => 'integer',
        'año' => 'integer',
    ];

    /**
     * Relación con Establecimiento
     */
    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id');
    }

    /**
     * Obtener el total de gastos
     */
    public function getTotalGastosAttribute(): float
    {
        return (float) ($this->aseo + $this->administracion + $this->otros_gastos);
    }

    /**
     * Obtener el nombre del mes
     */
    public function getNombreMesAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $meses[$this->mes] ?? 'N/A';
    }

    /**
     * Obtener período en formato legible
     */
    public function getPeriodoAttribute(): string
    {
        return $this->nombre_mes . ' ' . $this->año;
    }
}
