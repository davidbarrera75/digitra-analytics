<?php

namespace App\Filament\Widgets;

use App\Jobs\GenerarResumenMensualIA;
use App\Models\ResumenMensualIA;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ResumenMensualIAWidget extends Widget
{
    protected static string $view = 'filament.widgets.resumen-mensual-ia';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public bool $generando = false;

    /**
     * Visible para todos los usuarios autenticados
     */
    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * Generar nuevo análisis IA
     */
    public function generarAnalisis(): void
    {
        $user = Auth::user();
        $mes = now()->month;
        $año = now()->year;

        // Verificar si ya existe un resumen para este mes
        if (ResumenMensualIA::existeResumen($user->id, $mes, $año)) {
            Notification::make()
                ->warning()
                ->title('Ya existe un análisis para este mes')
                ->body('Puedes regenerarlo el próximo mes.')
                ->send();
            return;
        }

        // Verificar que el usuario tenga tenant
        if (!$user->tenant) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('No tienes un tenant configurado.')
                ->send();
            return;
        }

        // Despachar el job
        GenerarResumenMensualIA::dispatch($user, $mes, $año);

        Notification::make()
            ->success()
            ->title('Análisis en proceso')
            ->body('Tu análisis IA se está generando. Recarga la página en unos segundos.')
            ->send();

        // Marcar como generando
        $this->generando = true;
    }

    /**
     * Obtener datos para la vista
     */
    protected function getViewData(): array
    {
        $resumen = ResumenMensualIA::obtenerMasReciente(Auth::id());

        // Verificar si el resumen es del mes actual
        $esDelMesActual = $resumen &&
            $resumen->mes === now()->month &&
            $resumen->año === now()->year;

        return [
            'resumen' => $resumen,
            'existe' => $resumen !== null,
            'es_del_mes_actual' => $esDelMesActual,
            'periodo' => $resumen?->periodo ?? 'Sin datos',
            'contenido' => $resumen?->contenido ?? '',
            'contenido_html' => $resumen?->contenido_html ?? '',
            'generado_en' => $resumen?->generado_en?->diffForHumans() ?? 'Nunca',
            'tokens_usados' => $resumen?->tokens_usados ?? 0,
            'generando' => $this->generando,
        ];
    }
}
