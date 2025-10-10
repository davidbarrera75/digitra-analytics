<?php

namespace App\Filament\Widgets;

use App\Models\Digitra\User as DigitraUser;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class SaludUsuariosStats extends BaseWidget
{
    protected static ?int $sort = 0;

    /**
     * Solo visible para super admins
     */
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    protected function getStats(): array
    {
        // Cachear estadísticas por 5 minutos
        $stats = Cache::remember('salud_usuarios_stats', 300, function () {
            $totalUsuarios = DigitraUser::count();

            // Contadores por estado de actividad
            $activos = 0;
            $enRiesgo = 0;
            $altoRiesgo = 0;
            $inactivos = 0;
            $sinActividad = 0;

            DigitraUser::with(['reservas' => function ($query) {
                $query->orderBy('check_in', 'desc')->limit(1);
            }])->chunk(100, function ($usuarios) use (&$activos, &$enRiesgo, &$altoRiesgo, &$inactivos, &$sinActividad) {
                foreach ($usuarios as $usuario) {
                    $ultimaReserva = $usuario->reservas()->orderBy('check_in', 'desc')->first();

                    if (!$ultimaReserva) {
                        $sinActividad++;
                        continue;
                    }

                    $diasSinActividad = now()->diffInDays($ultimaReserva->check_in);

                    if ($diasSinActividad <= 30) {
                        $activos++;
                    } elseif ($diasSinActividad <= 90) {
                        $enRiesgo++;
                    } elseif ($diasSinActividad <= 180) {
                        $altoRiesgo++;
                    } else {
                        $inactivos++;
                    }
                }
            });

            return [
                'total' => $totalUsuarios,
                'activos' => $activos,
                'en_riesgo' => $enRiesgo,
                'alto_riesgo' => $altoRiesgo,
                'inactivos' => $inactivos,
                'sin_actividad' => $sinActividad,
            ];
        });

        $total = $stats['total'];
        $porcentajeActivos = $total > 0 ? round(($stats['activos'] / $total) * 100, 1) : 0;
        $porcentajeEnRiesgo = $total > 0 ? round(($stats['en_riesgo'] / $total) * 100, 1) : 0;
        $porcentajeAltoRiesgo = $total > 0 ? round(($stats['alto_riesgo'] / $total) * 100, 1) : 0;
        $porcentajeInactivos = $total > 0 ? round(($stats['inactivos'] / $total) * 100, 1) : 0;

        return [
            Stat::make('🟢 Usuarios Activos', $stats['activos'])
                ->description("Reservas en últimos 30 días ({$porcentajeActivos}%)")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart([5, 10, 15, 20, 25, 30, $stats['activos']]),

            Stat::make('🟡 En Riesgo', $stats['en_riesgo'])
                ->description("Sin reservas 1-3 meses ({$porcentajeEnRiesgo}%)")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('🟠 Alto Riesgo', $stats['alto_riesgo'])
                ->description("Sin reservas 3-6 meses ({$porcentajeAltoRiesgo}%)")
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('🔴 Inactivos', $stats['inactivos'])
                ->description("Sin reservas +6 meses ({$porcentajeInactivos}%)")
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('gray'),

            Stat::make('⚫ Sin Actividad', $stats['sin_actividad'])
                ->description('Nunca han tenido reservas')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('gray'),
        ];
    }
}
