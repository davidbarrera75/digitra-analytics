<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Aniversarios extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cake';

    protected static string $view = 'filament.pages.aniversarios';

    protected static ?string $navigationGroup = 'Datos de Digitra';

    protected static ?int $navigationSort = 5;

    /**
     * Etiqueta de navegación dinámica según tipo de usuario
     */
    public static function getNavigationLabel(): string
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return 'Aniversarios';
        }

        return 'Vencimientos';
    }

    /**
     * Título dinámico según tipo de usuario
     */
    public function getTitle(): string
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return '🎂 Aniversarios de Establecimientos';
        }

        return '📅 Vencimientos de Establecimientos';
    }

    /**
     * Obtener subtítulo personalizado
     */
    public function getSubheading(): ?string
    {
        $user = auth()->user();

        if ($user && !$user->isSuperAdmin()) {
            return 'Establecimientos que van a cumplir un año desde su creación';
        }

        return null;
    }

    public function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\AniversariosStats::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\EstablecimientosAniversarioTable::class,
        ];
    }
}
