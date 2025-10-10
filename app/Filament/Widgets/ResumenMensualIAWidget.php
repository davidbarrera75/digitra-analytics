<?php

namespace App\Filament\Widgets;

use App\Models\ResumenMensualIA;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ResumenMensualIAWidget extends Widget
{
    protected static string $view = 'filament.widgets.resumen-mensual-ia';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    /**
     * Visible para todos los usuarios autenticados
     */
    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * Obtener datos para la vista
     */
    protected function getViewData(): array
    {
        $resumen = ResumenMensualIA::obtenerMasReciente(Auth::id());

        return [
            'resumen' => $resumen,
            'existe' => $resumen !== null,
            'periodo' => $resumen?->periodo ?? 'Sin datos',
            'contenido' => $resumen?->contenido ?? '',
            'contenido_html' => $resumen?->contenido_html ?? '',
            'generado_en' => $resumen?->generado_en?->diffForHumans() ?? 'Nunca',
            'tokens_usados' => $resumen?->tokens_usados ?? 0,
        ];
    }
}
