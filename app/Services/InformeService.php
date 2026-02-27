<?php

namespace App\Services;

use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use App\Models\Digitra\Huesped;
use App\Models\Digitra\User as DigitraUser;
use App\Models\GastoMensual;
use App\Models\ReservaIgnorada;
use App\Models\ReservaCorregida;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InformeService
{
    /**
     * Aplicar filtros de calidad de datos a una query de reservas
     * Excluye reservas ignoradas
     */
    private function aplicarFiltrosCalidad($query)
    {
        // Excluir reservas ignoradas usando subquery (no carga IDs a memoria)
        $query->whereNotIn('id', ReservaIgnorada::query()->select('reserva_id'));

        return $query;
    }

    /**
     * Obtener precio de una reserva (aplicando correcciones si existen)
     */
    private function obtenerPrecioReserva($reserva): float
    {
        $correccion = ReservaCorregida::where('reserva_id', $reserva->id)->first();

        if ($correccion) {
            return (float) $correccion->precio_corregido;
        }

        return (float) $reserva->precio;
    }

    /**
     * Aplicar precio corregido a una reserva si existe corrección
     */
    private function aplicarCorrecionPrecio($reserva)
    {
        $correccion = ReservaCorregida::where('reserva_id', $reserva->id)->first();

        if ($correccion) {
            $reserva->precio = $correccion->precio_corregido;
        }

        return $reserva;
    }

    /**
     * Generar datos completos para el informe
     *
     * @param Carbon|null $fechaInicio
     * @param Carbon|null $fechaFin
     * @param int|null $establecimientoId ID del establecimiento específico, null para todos
     */
    public function generarDatosInforme(?Carbon $fechaInicio = null, ?Carbon $fechaFin = null, ?int $establecimientoId = null): array
    {
        $fechaInicio = $fechaInicio ?? now()->subMonths(3)->startOfMonth();
        $fechaFin = $fechaFin ?? now()->endOfMonth();
        // Convertir establecimientoId (id de tabla) a digitra_id para filtrar reservas
        $digitraIdEstablecimiento = null;
        if ($establecimientoId) {
            $establecimiento = Establecimiento::find($establecimientoId);
            if ($establecimiento) {
                $digitraIdEstablecimiento = $establecimiento->digitra_id;
            }
        }

        // Obtener digitra_user_id del tenant actual
        $digitraUserId = digitra_user_id();

        // VALIDACIÓN DE SEGURIDAD: Si se especifica un establecimiento, verificar que pertenece al tenant
        if ($establecimientoId && $digitraUserId) {
            $establecimiento = Establecimiento::find($establecimientoId);
            if (!$establecimiento || $establecimiento->user_id !== $digitraUserId) {
                throw new \Exception('No tienes permiso para acceder a este establecimiento.');
            }
        }

        // Cachear datos por 10 minutos con clave única por rango de fechas, tenant y establecimiento
        $cacheKey = 'informe_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd')
                    . '_tenant' . ($digitraUserId ?? 'all')
                    . '_estab' . ($establecimientoId ?? 'all');

        return Cache::remember($cacheKey, 600, function () use ($fechaInicio, $fechaFin, $digitraUserId, $establecimientoId, $digitraIdEstablecimiento) {
            // Calcular meses de forma más precisa
            $diffInMonths = $fechaInicio->diffInMonths($fechaFin);
            // Si la diferencia en días no es exactamente múltiplo de 30, considerar mes parcial
            $diffInDays = $fechaInicio->diffInDays($fechaFin);
            $mesesCalculados = $diffInDays < 30 ? 1 : ($diffInMonths > 0 ? $diffInMonths : 1);

            return [
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin,
                    'dias' => $diffInDays,
                    'meses' => $mesesCalculados,
                ],
                'establecimiento' => $establecimientoId ? Establecimiento::find($establecimientoId) : null,
                'estadisticas_generales' => $this->obtenerEstadisticasGenerales($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'reservas' => $this->obtenerDatosReservas($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'reservas_detalle' => $this->obtenerDetalleReservas($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'establecimientos' => $this->obtenerDatosEstablecimientos($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'usuarios' => $this->obtenerDatosUsuarios($fechaInicio, $fechaFin),
                'tendencias' => $this->obtenerTendencias($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'noches_por_mes' => $this->obtenerNochesPorMes($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'top_propiedades' => $this->obtenerTopPropiedades($fechaInicio, $fechaFin, 10, $establecimientoId, $digitraIdEstablecimiento),
                'aniversarios' => $this->obtenerAniversarios($fechaInicio, $fechaFin),
                'alertas' => $this->validarCalidadDatos($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
                'gastos' => $this->obtenerGastosMensuales($fechaInicio, $fechaFin, $establecimientoId, $digitraIdEstablecimiento),
            ];
        });
    }

    /**
     * Estadísticas generales
     */
    private function obtenerEstadisticasGenerales(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        // Query base para reservas
        $reservasQuery = Reserva::whereBetween('check_in', [$fechaInicio, $fechaFin]);

        // Filtrar por establecimiento si se especifica
        if ($establecimientoId) {
            $reservasQuery->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
            // VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
            $digitraUserId = digitra_user_id();
            if ($digitraUserId && (!$establecimiento || $establecimiento->user_id !== $digitraUserId)) {
    // Si el establecimiento no existe o no pertenece al usuario, forzar query vacío
    $reservasQuery->whereRaw('1 = 0');
}
    } else {
            // Filtrar por tenant
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $reservasQuery->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }

        // APLICAR FILTROS DE CALIDAD
        $this->aplicarFiltrosCalidad($reservasQuery);

        $totalReservas = $reservasQuery->count();

        // Calcular ingresos: suma en DB + ajuste por correcciones (sin N+1)
        $totalIngresos = (float) (clone $reservasQuery)->sum('total_pagado');

        // Aplicar correcciones de precio en bulk
        $reservaIds = (clone $reservasQuery)->pluck('id')->toArray();
        if (!empty($reservaIds)) {
            $correcciones = ReservaCorregida::whereIn('reserva_id', $reservaIds)->get();
            foreach ($correcciones as $correccion) {
                $totalIngresos = $totalIngresos
                    - (float) $correccion->precio_original
                    + (float) $correccion->precio_corregido;
            }
        }

        // Huéspedes
            $huespedesQuery = Huesped::whereHas('reserva', function ($query) use ($fechaInicio, $fechaFin, $digitraIdEstablecimiento, $digitraUserId) {
                $query->whereBetween('check_in', [$fechaInicio, $fechaFin]);
                if ($digitraIdEstablecimiento) {
                    $query->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
                } else {
                    if ($digitraUserId) {
                        $query->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                            $q->where('user_id', $digitraUserId);
                        });
                    }
                }
            });
            $totalHuespedes = $huespedesQuery->distinct('numero_documento')->count();

        // Establecimientos: si es un informe específico, solo 1, si no, contar los del tenant
        if ($establecimientoId) {
            $totalEstablecimientos = 1;
        } else {
            $digitraUserId = digitra_user_id();
            $establecimientosQuery = Establecimiento::activos();
            if ($digitraUserId) {
                $establecimientosQuery->where('user_id', $digitraUserId);
            }
            $totalEstablecimientos = $establecimientosQuery->count();
        }

        $totalUsuarios = DigitraUser::conEstablecimientos()->count();

        // Calcular promedios
        $promedioReservasPorDia = $totalReservas / max($fechaInicio->diffInDays($fechaFin), 1);
        $promedioIngresosPorReserva = $totalReservas > 0 ? $totalIngresos / $totalReservas : 0;

        return [
            'total_reservas' => $totalReservas,
            'total_ingresos' => $totalIngresos,
            'total_huespedes' => $totalHuespedes,
            'total_establecimientos' => $totalEstablecimientos,
            'total_usuarios' => $totalUsuarios,
            'promedio_reservas_por_dia' => round($promedioReservasPorDia, 2),
            'promedio_ingresos_por_reserva' => round($promedioIngresosPorReserva, 2),
        ];
    }

    /**
     * Datos de reservas
     */
    private function obtenerDatosReservas(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        $reservasQuery = Reserva::whereBetween('check_in', [$fechaInicio, $fechaFin]);

        // Filtrar por establecimiento si se especifica
        if ($establecimientoId) {
            $reservasQuery->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
            // VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
$digitraUserId = digitra_user_id();
if ($digitraUserId && (!$establecimiento || $establecimiento->user_id !== $digitraUserId)) {
    // Si el establecimiento no existe o no pertenece al usuario, forzar query vacío
    $reservasQuery->whereRaw('1 = 0');
}
    } else {
            // Filtrar por tenant
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $reservasQuery->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }

        // APLICAR FILTROS DE CALIDAD
        $this->aplicarFiltrosCalidad($reservasQuery);

        return [
            'total' => $reservasQuery->count(),
            'activas' => (clone $reservasQuery)->where('is_active', true)->count(),
            'completadas' => (clone $reservasQuery)->where('check_out', '<', now())->count(),
            'futuras' => (clone $reservasQuery)->where('check_in', '>', now())->count(),
            'con_seguro' => (clone $reservasQuery)->where('seguro', true)->count(),
            'tra_enviados' => (clone $reservasQuery)->where('tra_send', true)->count(),
        ];
    }

    /**
     * Detalle completo de reservas para el informe
     */
    private function obtenerDetalleReservas(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        $reservasQuery = Reserva::with(['establecimiento', 'huespedes'])
            ->whereBetween('check_in', [$fechaInicio, $fechaFin]);

        // Filtrar por establecimiento si se especifica
        if ($establecimientoId) {
            $reservasQuery->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
            // VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
            $digitraUserId = digitra_user_id();
            if ($digitraUserId && (!$establecimiento || $establecimiento->user_id !== $digitraUserId)) {
    // Si el establecimiento no existe o no pertenece al usuario, forzar query vacío
    $reservasQuery->whereRaw('1 = 0');
}
        } else {
            // Filtrar por tenant
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $reservasQuery->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }


    // APLICAR FILTROS DE CALIDAD
    $this->aplicarFiltrosCalidad($reservasQuery);

        $reservas = $reservasQuery->orderBy('check_in', 'desc')->get();

        // Precargar correcciones en bulk (evita N+1)
        $reservaIds = $reservas->pluck('id')->toArray();
        $correcciones = !empty($reservaIds)
            ? ReservaCorregida::whereIn('reserva_id', $reservaIds)
                ->get()
                ->keyBy('reserva_id')
            : collect();

        return $reservas->map(function ($reserva) use ($correcciones) {
                $primerHuesped = $reserva->huespedes->first();

                // Usar corrección precargada si existe
                $correccion = $correcciones->get($reserva->id);
                $precioFinal = $correccion
                    ? (float) $correccion->precio_corregido
                    : (float) $reserva->precio;

                return [
                    'id' => $reserva->id,
                    'check_in' => $reserva->check_in,
                    'check_out' => $reserva->check_out,
                    'precio' => $precioFinal,
                    'establecimiento' => $reserva->establecimiento ? $reserva->establecimiento->nombre : 'N/A',
                    'huesped' => $primerHuesped ? $primerHuesped->nombre_completo : 'N/A',
                    'noches' => $reserva->check_in && $reserva->check_out
                        ? Carbon::parse($reserva->check_in)->diffInDays(Carbon::parse($reserva->check_out))
                        : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Datos de establecimientos
     */
    private function obtenerDatosEstablecimientos(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        // Si es un informe específico de un establecimiento
        if ($establecimientoId) {
            $establecimiento = Establecimiento::find($establecimientoId);
            if (!$establecimiento) {
                return [
                    'total_activos' => 0,
                    'con_auto_tra' => 0,
                    'con_documentacion' => 0,
                    'con_reservas_en_periodo' => 0,
                ];
            }

            return [
                'total_activos' => 1,
                'con_auto_tra' => $establecimiento->auto_send_tra ? 1 : 0,
                'con_documentacion' => $establecimiento->documentacion ? 1 : 0,
                'con_reservas_en_periodo' => $establecimiento->reservas()
                    ->whereBetween('check_in', [$fechaInicio, $fechaFin])
                    ->exists() ? 1 : 0,
            ];
        }

        // Informe general del tenant
        $digitraUserId = digitra_user_id();
        $queryBase = Establecimiento::activos();

        if ($digitraUserId) {
            $queryBase->where('user_id', $digitraUserId);
        }

        return [
            'total_activos' => (clone $queryBase)->count(),
            'con_auto_tra' => (clone $queryBase)->where('auto_send_tra', true)->count(),
            'con_documentacion' => (clone $queryBase)->where('documentacion', true)->count(),
            'con_reservas_en_periodo' => Reserva::whereBetween('check_in', [$fechaInicio, $fechaFin])
                ->whereIn(
                    'establecimiento_digitra_id',
                    (clone $queryBase)->pluck('digitra_id')->toArray()
                )
                ->distinct('establecimiento_digitra_id')
                ->count('establecimiento_digitra_id'),
        ];
    }

    /**
     * Datos de usuarios
     */
    private function obtenerDatosUsuarios(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        return [
            'total_con_propiedades' => DigitraUser::conEstablecimientos()->count(),
            'colasistencia' => DigitraUser::where('is_colasistencia', true)->count(),
            'con_reservas_en_periodo' => DB::connection('sqlite')
                ->table('digitra_reservas')
                ->whereBetween('check_in', [$fechaInicio, $fechaFin])
                ->join('digitra_establecimientos', 'digitra_reservas.establecimiento_digitra_id', '=', 'digitra_establecimientos.digitra_id')
                ->where('digitra_establecimientos.deleted', false)
                ->distinct('digitra_establecimientos.user_id')
                ->count('digitra_establecimientos.user_id'),
        ];
    }

    /**
     * Tendencias de reservas por mes
     */
    private function obtenerTendencias(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        // Query base
        $query = Reserva::query();

        // Filtrar por establecimiento si se especifica
        if ($establecimientoId) {
            $query->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
// VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
            $digitraUserId = digitra_user_id();
            if ($digitraUserId && (!$establecimiento || $establecimiento->user_id !== $digitraUserId)) {
    // Si el establecimiento no existe o no pertenece al usuario, forzar query vacío
    $query->whereRaw('1 = 0');
}
    } else {
            // Filtrar por tenant
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $query->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }

        // APLICAR FILTROS DE CALIDAD
        $this->aplicarFiltrosCalidad($query);

        $data = Trend::query($query)
            ->between(
                start: $fechaInicio,
                end: $fechaFin,
            )
            ->perMonth()
            ->count();

        $labels = [];
        $valores = [];

        foreach ($data as $punto) {
            $labels[] = Carbon::parse($punto->date)->translatedFormat('M Y');
            $valores[] = $punto->aggregate;
        }

        return [
            'labels' => $labels,
            'valores' => $valores,
        ];
    }

    /**
     * Total de noches reservadas por mes
     */
    private function obtenerNochesPorMes(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        // Query base para reservas
        $reservasQuery = Reserva::query()
            ->whereBetween('check_in', [$fechaInicio, $fechaFin])
            ->whereNotNull('check_out');

        // Filtrar por establecimiento si se especifica
        if ($establecimientoId) {
            $reservasQuery->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
            // VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
$digitraUserId = digitra_user_id();
if ($digitraUserId && (!$establecimiento || $establecimiento->user_id !== $digitraUserId)) {
    // Si el establecimiento no existe o no pertenece al usuario, forzar query vacío
    $reservasQuery->whereRaw('1 = 0');
}
    } else {
            // Filtrar por tenant
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $reservasQuery->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }

        // APLICAR FILTROS DE CALIDAD
        $this->aplicarFiltrosCalidad($reservasQuery);

        // Obtener todas las reservas del período
        $reservas = $reservasQuery->get(['check_in', 'check_out']);

        // Agrupar noches por mes
        $nochesPorMes = [];
        $labels = [];

        // Inicializar array con todos los meses del período
        $mesActual = $fechaInicio->copy()->startOfMonth();
        $mesFin = $fechaFin->copy()->endOfMonth();

        while ($mesActual <= $mesFin) {
            $mesKey = $mesActual->format('Y-m');
            $mesLabel = $mesActual->translatedFormat('M Y');
            $nochesPorMes[$mesKey] = 0;
            $labels[$mesKey] = $mesLabel;
            $mesActual->addMonth();
        }

        // Calcular noches por cada reserva y asignarlas al mes correspondiente
        foreach ($reservas as $reserva) {
            $checkIn = Carbon::parse($reserva->check_in);
            $checkOut = Carbon::parse($reserva->check_out);
            $totalNoches = $checkIn->diffInDays($checkOut);

            // Asignar las noches al mes del check-in
            $mesKey = $checkIn->format('Y-m');
            if (isset($nochesPorMes[$mesKey])) {
                $nochesPorMes[$mesKey] += $totalNoches;
            }
        }

        return [
            'labels' => array_values($labels),
            'valores' => array_values($nochesPorMes),
            'total_noches' => array_sum($nochesPorMes),
        ];
    }

    /**
     * Top 10 propiedades por reservas
     */
    private function obtenerTopPropiedades(Carbon $fechaInicio, Carbon $fechaFin, int $limit = 10, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        // Si es un informe específico, solo ese establecimiento
        if ($establecimientoId) {
            $establecimiento = Establecimiento::find($establecimientoId);
            if (!$establecimiento) {
                return [];
            }

            $reservasCount = $establecimiento->reservas()
                ->whereBetween('check_in', [$fechaInicio, $fechaFin])
                ->count();

            return [[
                'nombre' => $establecimiento->nombre,
                'propietario' => $establecimiento->user_name ?? 'N/A',
                'reservas' => $reservasCount,
                'rnt' => $establecimiento->rnt,
            ]];
        }

        // Informe general del tenant
        $digitraUserId = digitra_user_id();
        $query = Establecimiento::activos();

        if ($digitraUserId) {
            $query->where('user_id', $digitraUserId);
        }

        return $query
            ->withCount(['reservas' => function ($query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('check_in', [$fechaInicio, $fechaFin]);
            }])
        
            ->orderByDesc('reservas_count')
            ->limit($limit)
            ->get()
            ->map(function ($establecimiento) {
                return [
                    'nombre' => $establecimiento->nombre,
                    'propietario' => $establecimiento->user_name ?? 'N/A',
                    'reservas' => $establecimiento->reservas_count,
                    'rnt' => $establecimiento->rnt,
                ];
            })
            ->toArray();
    }

    /**
     * Aniversarios en el período
     */
    private function obtenerAniversarios(Carbon $fechaInicio, Carbon $fechaFin): array
    {
        $dias = $fechaFin->diffInDays(now());

        return [
            'proximos_30_dias' => Establecimiento::activos()
                ->proximosAniversarios(30)
                ->count(),
            'en_periodo' => Establecimiento::activos()
                ->whereRaw("date(digitra_created_at, '+1 year') BETWEEN ? AND ?", [
                    $fechaInicio->format('Y-m-d'),
                    $fechaFin->format('Y-m-d')
                ])
                ->count(),
            'ya_cumplieron' => Establecimiento::activos()
                ->yaCumplieronAnio()
                ->count(),
        ];
    }

    /**
     * Generar insights (análisis inteligente de datos)
     */
    public function generarInsights(array $datos): array
    {
        $insights = [];

        // Insight 1: Tasa de ocupación
        $diasPeriodo = $datos['periodo']['dias'];
        $totalReservas = $datos['estadisticas_generales']['total_reservas'];
        $totalPropiedades = $datos['estadisticas_generales']['total_establecimientos'];

        if ($totalPropiedades > 0 && $diasPeriodo > 0) {
            $tasaOcupacion = ($totalReservas / ($totalPropiedades * $diasPeriodo)) * 100;
            $insights[] = [
                'icono' => '📊',
                'titulo' => 'Tasa de Ocupación',
                'valor' => round($tasaOcupacion, 2) . '%',
                'descripcion' => $tasaOcupacion > 50
                    ? 'Excelente tasa de ocupación para el período analizado.'
                    : 'Hay oportunidad para mejorar la ocupación de las propiedades.',
            ];
        }

        // Insight 2: Crecimiento
        $tendencias = $datos['tendencias']['valores'];
        if (count($tendencias) >= 2) {
            $primerMes = $tendencias[0];
            $ultimoMes = end($tendencias);
            $crecimiento = $primerMes > 0 ? (($ultimoMes - $primerMes) / $primerMes) * 100 : 0;

            $insights[] = [
                'icono' => $crecimiento >= 0 ? '📈' : '📉',
                'titulo' => 'Tendencia de Crecimiento',
                'valor' => ($crecimiento >= 0 ? '+' : '') . round($crecimiento, 1) . '%',
                'descripcion' => $crecimiento >= 0
                    ? 'Las reservas muestran una tendencia positiva.'
                    : 'Se recomienda implementar estrategias para incrementar reservas.',
            ];
        }

        // Insight 3: Ingresos promedio
        $ingresoPromedio = $datos['estadisticas_generales']['promedio_ingresos_por_reserva'];
        $insights[] = [
            'icono' => '💰',
            'titulo' => 'Ingreso Promedio por Reserva',
            'valor' => '$' . number_format($ingresoPromedio, 0, ',', '.'),
            'descripcion' => 'Valor promedio generado por cada reserva en el período.',
        ];

        // Insight 4: Adopción de tecnología
        $autoTRA = $datos['establecimientos']['con_auto_tra'];
        $totalEstab = $datos['establecimientos']['total_activos'];
        $porcentajeAutoTRA = $totalEstab > 0 ? ($autoTRA / $totalEstab) * 100 : 0;

        $insights[] = [
            'icono' => '🤖',
            'titulo' => 'Automatización TRA',
            'valor' => round($porcentajeAutoTRA, 1) . '%',
            'descripcion' => $porcentajeAutoTRA > 70
                ? 'Alto nivel de automatización en el envío de TRA.'
                : 'Oportunidad para incrementar la automatización.',
        ];

        return $insights;
    }

    /**
     * Validar calidad de datos y detectar inconsistencias
     */
    private function validarCalidadDatos(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        $alertas = [];
        $advertencias = [];

        // Obtener todas las reservas del período
        $reservasQuery = Reserva::with(['establecimiento', 'huespedes'])
            ->whereBetween('check_in', [$fechaInicio, $fechaFin]);

        if ($establecimientoId) {
            $reservasQuery->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
            // VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
            $digitraUserId = digitra_user_id();
            if ($digitraUserId && (!$establecimiento || $establecimiento->user_id !== $digitraUserId)) {
    // Si el establecimiento no existe o no pertenece al usuario, forzar query vacío
    $reservasQuery->whereRaw('1 = 0');
}
    } else {
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $reservasQuery->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }

        $reservas = $reservasQuery->get();

        // 1. Detectar reservas duplicadas (mismo huésped, fechas muy cercanas)
        $reservasPorHuesped = [];
        foreach ($reservas as $reserva) {
            $primerHuesped = $reserva->huespedes->first();
            if ($primerHuesped) {
                $key = strtoupper(trim($primerHuesped->nombre_completo));
                if (!isset($reservasPorHuesped[$key])) {
                    $reservasPorHuesped[$key] = [];
                }
                $reservasPorHuesped[$key][] = $reserva;
            }
        }

        foreach ($reservasPorHuesped as $nombreHuesped => $reservasHuesped) {
            if (count($reservasHuesped) > 1) {
                // Revisar si hay fechas duplicadas o muy cercanas
                for ($i = 0; $i < count($reservasHuesped); $i++) {
                    for ($j = $i + 1; $j < count($reservasHuesped); $j++) {
                        $r1 = $reservasHuesped[$i];
                        $r2 = $reservasHuesped[$j];

                        $checkIn1 = Carbon::parse($r1->check_in);
                        $checkIn2 = Carbon::parse($r2->check_in);

                        // Si las fechas son iguales o muy cercanas (mismo día)
                        if ($checkIn1->isSameDay($checkIn2)) {
                            $alertas[] = [
                                'tipo' => 'error',
                                'icono' => '🔴',
                                'titulo' => 'Reserva Duplicada Detectada',
                                'descripcion' => "El huésped '{$nombreHuesped}' tiene múltiples reservas con la misma fecha de check-in ({$checkIn1->format('d/m/Y')}). IDs: {$r1->id}, {$r2->id}",
                                'recomendacion' => 'Revisar en la base de datos y eliminar duplicados.',
                            ];
                        }
                    }
                }
            }
        }

        // 2. Validar noches por mes no excedan días del mes
        $nochesPorMes = [];
        foreach ($reservas as $reserva) {
            if ($reserva->check_in && $reserva->check_out) {
                $checkIn = Carbon::parse($reserva->check_in);
                $checkOut = Carbon::parse($reserva->check_out);
                $noches = $checkIn->diffInDays($checkOut);

                $mesKey = $checkIn->format('Y-m');
                if (!isset($nochesPorMes[$mesKey])) {
                    $nochesPorMes[$mesKey] = 0;
                }
                $nochesPorMes[$mesKey] += $noches;
            }
        }

        foreach ($nochesPorMes as $mesKey => $totalNoches) {
            $fecha = Carbon::createFromFormat('Y-m', $mesKey);
            $diasDelMes = $fecha->daysInMonth;

            if ($totalNoches > $diasDelMes) {
                $alertas[] = [
                    'tipo' => 'error',
                    'icono' => '🔴',
                    'titulo' => 'Inconsistencia en Noches Reservadas',
                    'descripcion' => "{$fecha->translatedFormat('F Y')} tiene {$totalNoches} noches reservadas, pero el mes solo tiene {$diasDelMes} días.",
                    'recomendacion' => 'Esto indica reservas duplicadas o errores en las fechas. Revisar detalle de reservas del mes.',
                ];
            }
        }

        // 3. Detectar fechas inválidas (check-out antes de check-in)
        foreach ($reservas as $reserva) {
            if ($reserva->check_in && $reserva->check_out) {
                $checkIn = Carbon::parse($reserva->check_in);
                $checkOut = Carbon::parse($reserva->check_out);

                if ($checkOut->lt($checkIn)) {
                    $primerHuesped = $reserva->huespedes->first();
                    $nombreHuesped = $primerHuesped ? $primerHuesped->nombre_completo : 'N/A';
                    $alertas[] = [
                        'tipo' => 'error',
                        'icono' => '🔴',
                        'titulo' => 'Fechas Inválidas',
                        'descripcion' => "Reserva ID {$reserva->id} ({$nombreHuesped}) tiene check-out ({$checkOut->format('d/m/Y')}) antes del check-in ({$checkIn->format('d/m/Y')}).",
                        'recomendacion' => 'Corregir las fechas de la reserva.',
                    ];
                }
            }
        }

        // 4. Detectar precios anormales
        $precios = $reservas->pluck('precio')->filter()->values();
        if ($precios->count() > 0) {
            $precioPromedio = $precios->avg();
            $precioMax = $precios->max();
            $precioMin = $precios->min();

            foreach ($reservas as $reserva) {
                if ($reserva->precio) {
                    // Precio muy bajo (menos del 10% del promedio)
                    if ($reserva->precio < ($precioPromedio * 0.1) && $reserva->precio > 0) {
                        $primerHuesped = $reserva->huespedes->first();
                        $nombreHuesped = $primerHuesped ? $primerHuesped->nombre_completo : 'N/A';
                        $advertencias[] = [
                            'tipo' => 'warning',
                            'icono' => '⚠️',
                            'titulo' => 'Precio Inusualmente Bajo',
                            'descripcion' => "Reserva ID {$reserva->id} ({$nombreHuesped}) tiene precio de $" . number_format($reserva->precio, 0, ',', '.') . " (muy por debajo del promedio de $" . number_format($precioPromedio, 0, ',', '.') . ").",
                            'recomendacion' => 'Verificar si el precio es correcto.',
                        ];
                    }

                    // Precio muy alto (más de 5 veces el promedio)
                    if ($reserva->precio > ($precioPromedio * 5)) {
                        $primerHuesped = $reserva->huespedes->first();
                        $nombreHuesped = $primerHuesped ? $primerHuesped->nombre_completo : 'N/A';
                        $advertencias[] = [
                            'tipo' => 'warning',
                            'icono' => '⚠️',
                            'titulo' => 'Precio Inusualmente Alto',
                            'descripcion' => "Reserva ID {$reserva->id} ({$nombreHuesped}) tiene precio de $" . number_format($reserva->precio, 0, ',', '.') . " (mucho más alto que el promedio).",
                            'recomendacion' => 'Verificar si el precio es correcto o si es una reserva especial.',
                        ];
                    }
                }
            }
        }

        // 5. Detectar reservas sin check-out
        $sinCheckOut = $reservas->filter(function ($reserva) {
            return !$reserva->check_out;
        });

        if ($sinCheckOut->count() > 0) {
            $advertencias[] = [
                'tipo' => 'warning',
                'icono' => '⚠️',
                'titulo' => 'Reservas sin Check-Out',
                'descripcion' => "Hay {$sinCheckOut->count()} reserva(s) sin fecha de check-out definida.",
                'recomendacion' => 'Completar la información de check-out para cálculos precisos de noches.',
            ];
        }

        // 6. Detectar solapamientos en la misma propiedad
        if ($establecimientoId) {
            $reservasPorFecha = $reservas->sortBy('check_in')->values();

            for ($i = 0; $i < $reservasPorFecha->count() - 1; $i++) {
                $actual = $reservasPorFecha[$i];
                $siguiente = $reservasPorFecha[$i + 1];

                if ($actual->check_out && $siguiente->check_in) {
                    $checkOutActual = Carbon::parse($actual->check_out);
                    $checkInSiguiente = Carbon::parse($siguiente->check_in);

                    // Si el check-in de la siguiente es antes del check-out de la actual
                    if ($checkInSiguiente->lt($checkOutActual)) {
                        $huesped1 = $actual->huespedes->first();
                        $huesped2 = $siguiente->huespedes->first();
                        $nombreHuesped1 = $huesped1 ? $huesped1->nombre_completo : 'N/A';
                        $nombreHuesped2 = $huesped2 ? $huesped2->nombre_completo : 'N/A';

                        $advertencias[] = [
                            'tipo' => 'warning',
                            'icono' => '⚠️',
                            'titulo' => 'Posible Solapamiento de Reservas',
                            'descripcion' => "Reserva de {$nombreHuesped1} (check-out: {$checkOutActual->format('d/m/Y')}) se solapa con reserva de {$nombreHuesped2} (check-in: {$checkInSiguiente->format('d/m/Y')}).",
                            'recomendacion' => 'Verificar si las fechas son correctas.',
                        ];
                    }
                }
            }
        }

        return [
            'alertas' => $alertas,
            'advertencias' => $advertencias,
            'total_alertas' => count($alertas),
            'total_advertencias' => count($advertencias),
            'tiene_problemas' => count($alertas) > 0 || count($advertencias) > 0,
        ];
    }

    /**
     * Obtener gastos mensuales del período
     */
    private function obtenerGastosMensuales(Carbon $fechaInicio, Carbon $fechaFin, ?int $establecimientoId = null, ?int $digitraIdEstablecimiento = null): array
    {
        // Si es un informe de un establecimiento específico
        if ($establecimientoId) {
            // Obtener meses del período
            $mesesPeriodo = [];
            $mesActual = $fechaInicio->copy()->startOfMonth();
            $mesFin = $fechaFin->copy()->endOfMonth();

            while ($mesActual <= $mesFin) {
                $mesesPeriodo[] = [
                    'mes' => $mesActual->month,
                    'año' => $mesActual->year,
                    'nombre' => $mesActual->translatedFormat('F Y'),
                ];
                $mesActual->addMonth();
            }

            // Obtener gastos del establecimiento en el período
            $gastos = GastoMensual::where('establecimiento_id', $establecimientoId)
                ->where(function ($query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('año', [$fechaInicio->year, $fechaFin->year])
                        ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                            $q->where('año', $fechaInicio->year)
                                ->where('mes', '>=', $fechaInicio->month);
                        })
                        ->orWhere(function ($q) use ($fechaInicio, $fechaFin) {
                            $q->where('año', $fechaFin->year)
                                ->where('mes', '<=', $fechaFin->month);
                        });
                })
                ->get()
                ->keyBy(function ($gasto) {
                    return $gasto->año . '-' . str_pad($gasto->mes, 2, '0', STR_PAD_LEFT);
                });

            // Mapear gastos por mes
            $gastosPorMes = [];
            $totalAseo = 0;
            $totalAdministracion = 0;
            $totalOtros = 0;
            $todasLasNotas = [];

            foreach ($mesesPeriodo as $mes) {
                $key = $mes['año'] . '-' . str_pad($mes['mes'], 2, '0', STR_PAD_LEFT);
                $gasto = $gastos->get($key);

                if ($gasto) {
                    $gastosPorMes[] = [
                        'periodo' => $mes['nombre'],
                        'aseo' => $gasto->aseo,
                        'administracion' => $gasto->administracion,
                        'otros_gastos' => $gasto->otros_gastos,
                        'total' => $gasto->total_gastos,
                        'notas' => $gasto->notas,
                    ];

                    $totalAseo += $gasto->aseo;
                    $totalAdministracion += $gasto->administracion;
                    $totalOtros += $gasto->otros_gastos;

                    if ($gasto->notas) {
                        $todasLasNotas[] = $mes['nombre'] . ': ' . $gasto->notas;
                    }
                }
            }

            return [
                'tiene_gastos' => count($gastosPorMes) > 0,
                'gastos_por_mes' => $gastosPorMes,
                'total_aseo' => $totalAseo,
                'total_administracion' => $totalAdministracion,
                'total_otros' => $totalOtros,
                'total_gastos' => $totalAseo + $totalAdministracion + $totalOtros,
                'notas' => $todasLasNotas,
            ];
        }

        // Informe general (sin gastos por ahora, ya que es por todas las propiedades)
        return [
            'tiene_gastos' => false,
            'gastos_por_mes' => [],
            'total_aseo' => 0,
            'total_administracion' => 0,
            'total_otros' => 0,
            'total_gastos' => 0,
            'notas' => [],
        ];
    }
}
