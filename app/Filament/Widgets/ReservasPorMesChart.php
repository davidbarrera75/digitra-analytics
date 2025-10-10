<?php

namespace App\Filament\Widgets;

use App\Models\Digitra\Reserva;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Cache;

class ReservasPorMesChart extends ChartWidget
{
    protected static ?string $heading = 'Reservas por Mes';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Obtener el digitra_user_id del tenant actual
        $digitraUserId = digitra_user_id();

        // Cachear datos por 10 minutos (incluir tenant en clave)
        $cacheKey = 'digitra_reservas_por_mes_chart_' . ($digitraUserId ?? 'all');
        return Cache::remember($cacheKey, 600, function () use ($digitraUserId) {
            // Query base
            $query = Reserva::query();

            // Filtrar por tenant si existe
            if ($digitraUserId) {
                $query->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }

            // Obtener datos de los últimos 12 meses
            $data = Trend::query($query)
                ->between(
                    start: now()->subMonths(11)->startOfMonth(),
                    end: now()->endOfMonth(),
                )
                ->perMonth()
                ->count();

            return [
                'datasets' => [
                    [
                        'label' => 'Reservas',
                        'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                        'backgroundColor' => '#10b981',
                        'borderColor' => '#10b981',
                    ],
                ],
                'labels' => $data->map(fn (TrendValue $value) => now()->createFromDate($value->date)->translatedFormat('M Y')),
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}
