<?php

namespace App\Filament\Widgets;

use App\Models\Digitra\Establecimiento;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AniversariosStats extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * Visible para todos los usuarios autenticados
     */
    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        $digitraUserId = digitra_user_id();

        // Super admin ve todos, usuarios regulares solo sus establecimientos
        $cacheKey = $isSuperAdmin
            ? 'digitra_aniversarios_stats_admin_all'
            : 'digitra_aniversarios_stats_user_' . $digitraUserId;

        $stats = Cache::remember($cacheKey, 300, function () use ($digitraUserId, $isSuperAdmin) {
            $baseQuery = Establecimiento::activos();

            // Solo filtrar por usuario si NO es super admin
            if (!$isSuperAdmin && $digitraUserId) {
                $baseQuery->where('user_id', $digitraUserId);
            }

            return [
                'hoy' => (clone $baseQuery)
                    ->whereRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) = CURDATE()')
                    ->count(),

                'proxima_semana' => (clone $baseQuery)
                    ->whereRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)')
                    ->count(),

                'proximo_mes' => (clone $baseQuery)
                    ->proximosAniversarios(30)
                    ->count(),

                'ya_cumplieron' => (clone $baseQuery)
                    ->yaCumplieronAnio()
                    ->count(),
            ];
        });

        // Textos diferentes según tipo de usuario
        if ($isSuperAdmin) {
            return [
                Stat::make('🎉 Aniversarios Hoy', $stats['hoy'])
                    ->description('Establecimientos que cumplen 1 año hoy')
                    ->descriptionIcon('heroicon-m-cake')
                    ->color($stats['hoy'] > 0 ? 'success' : 'gray')
                    ->chart([0, 1, 2, 1, 0, 1, $stats['hoy']]),

                Stat::make('📅 Próxima Semana (7 días)', $stats['proxima_semana'])
                    ->description('Aniversarios en los próximos 7 días')
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color($stats['proxima_semana'] > 0 ? 'warning' : 'gray'),

                Stat::make('📆 Próximo Mes (30 días)', $stats['proximo_mes'])
                    ->description('Aniversarios en los próximos 30 días')
                    ->descriptionIcon('heroicon-m-calendar')
                    ->color($stats['proximo_mes'] > 0 ? 'primary' : 'gray'),

                Stat::make('✅ Ya Cumplieron 1 Año', number_format($stats['ya_cumplieron']))
                    ->description('Total de establecimientos veteranos')
                    ->descriptionIcon('heroicon-m-trophy')
                    ->color('info')
                    ->chart([100, 200, 300, 350, 400, 450, $stats['ya_cumplieron']]),
            ];
        }

        // Para usuarios regulares
        return [
            Stat::make('📅 Vencimientos Hoy', $stats['hoy'])
                ->description('Establecimientos que cumplen 1 año hoy')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($stats['hoy'] > 0 ? 'success' : 'gray')
                ->chart([0, 1, 2, 1, 0, 1, $stats['hoy']]),

            Stat::make('📅 Próxima Semana (7 días)', $stats['proxima_semana'])
                ->description('Vencimientos en los próximos 7 días')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($stats['proxima_semana'] > 0 ? 'warning' : 'gray'),

            Stat::make('📆 Próximo Mes (30 días)', $stats['proximo_mes'])
                ->description('Vencimientos en los próximos 30 días')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($stats['proximo_mes'] > 0 ? 'primary' : 'gray'),

            Stat::make('✅ Ya Cumplieron 1 Año', number_format($stats['ya_cumplieron']))
                ->description('Total de establecimientos con más de 1 año')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info')
                ->chart([100, 200, 300, 350, 400, 450, $stats['ya_cumplieron']]),
        ];
    }
}
