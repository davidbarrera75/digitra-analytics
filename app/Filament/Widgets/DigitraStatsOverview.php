<?php

namespace App\Filament\Widgets;

use App\Models\Digitra\User as DigitraUser;
use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use App\Models\Digitra\Huesped;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class DigitraStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Obtener el digitra_user_id del tenant actual
        $digitraUserId = digitra_user_id();

        // Cachear estadísticas por 5 minutos (incluir tenant en clave de caché)
        $cacheKey = 'digitra_stats_overview_' . ($digitraUserId ?? 'all');
        $stats = Cache::remember($cacheKey, 300, function () use ($digitraUserId) {
            // Base query para establecimientos del tenant
            $establecimientosQuery = Establecimiento::activos();
            if ($digitraUserId) {
                $establecimientosQuery->where('user_id', $digitraUserId);
            }

            // Base query para reservas del tenant
            $reservasQuery = Reserva::query();
            if ($digitraUserId) {
                $reservasQuery->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }

            // Base query para huéspedes del tenant
            $huespedesQuery = Huesped::query();
            if ($digitraUserId) {
                $huespedesQuery->whereHas('reserva.establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }

            return [
                'totalUsuarios' => $digitraUserId ? 1 : DigitraUser::conEstablecimientos()->count(),
                'totalPropiedades' => $establecimientosQuery->count(),
                'totalReservas' => (clone $reservasQuery)->activas()->count(),
                'totalHuespedes' => $huespedesQuery->distinct('numero_documento')->count(),
                'reservasEsteMes' => (clone $reservasQuery)
                    ->whereMonth('check_in', now()->month)
                    ->whereYear('check_in', now()->year)
                    ->count(),
                'ingresosMes' => (clone $reservasQuery)
                    ->whereMonth('check_in', now()->month)
                    ->whereYear('check_in', now()->year)
                    ->sum('precio'),
            ];
        });

        // Extraer valores del caché
        $totalUsuarios = $stats['totalUsuarios'];
        $totalPropiedades = $stats['totalPropiedades'];
        $totalReservas = $stats['totalReservas'];
        $totalHuespedes = $stats['totalHuespedes'];
        $reservasEsteMes = $stats['reservasEsteMes'];
        $ingresosMes = $stats['ingresosMes'];

        return [
            Stat::make('Total Usuarios', number_format($totalUsuarios))
                ->description('Usuarios con propiedades')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([7, 12, 18, 24, 28, 32, $totalUsuarios]),

            Stat::make('Total Propiedades', number_format($totalPropiedades))
                ->description('Establecimientos activos')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->chart([50, 75, 120, 180, 240, 280, $totalPropiedades]),

            Stat::make('Reservas Activas', number_format($totalReservas))
                ->description('Total de reservas en sistema')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning')
                ->chart([100, 200, 350, 500, 700, 850, $totalReservas]),

            Stat::make('Reservas Este Mes', number_format($reservasEsteMes))
                ->description('Reservas de ' . now()->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Huéspedes Únicos', number_format($totalHuespedes))
                ->description('Total de huéspedes registrados')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Ingresos del Mes', '$' . number_format($ingresosMes, 0, ',', '.'))
                ->description('Ingresos de ' . now()->translatedFormat('F'))
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
