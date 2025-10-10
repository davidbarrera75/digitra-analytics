<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ResumenMensualIA extends Model
{
    protected $table = 'resumenes_mensuales_ia';

    protected $fillable = [
        'user_id',
        'mes',
        'año',
        'contenido',
        'datos_estadisticos',
        'tokens_usados',
        'generado_en',
    ];

    protected $casts = [
        'datos_estadisticos' => 'array',
        'generado_en' => 'datetime',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener el nombre del mes en español
     */
    public function getNombreMesAttribute(): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $meses[$this->mes] ?? 'Desconocido';
    }

    /**
     * Obtener el período completo (ej: "Octubre 2024")
     */
    public function getPeriodoAttribute(): string
    {
        return $this->nombre_mes . ' ' . $this->año;
    }

    /**
     * Obtener el contenido formateado como HTML
     */
    public function getContenidoHtmlAttribute(): string
    {
        return Str::markdown($this->contenido);
    }

    /**
     * Verificar si el resumen está desactualizado (más de 32 días)
     */
    public function getEstaDesactualizadoAttribute(): bool
    {
        if (!$this->generado_en) {
            return true;
        }

        return $this->generado_en->diffInDays(now()) > 32;
    }

    /**
     * Obtener el resumen más reciente de un usuario
     */
    public static function obtenerMasReciente($userId)
    {
        return static::where('user_id', $userId)
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->first();
    }

    /**
     * Verificar si ya existe un resumen para un mes/año específico
     */
    public static function existeResumen($userId, $mes, $año): bool
    {
        return static::where('user_id', $userId)
            ->where('mes', $mes)
            ->where('año', $año)
            ->exists();
    }
}
