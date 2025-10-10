<?php

namespace App\Filament\Resources\DigitraUserResource\Pages;

use App\Filament\Resources\DigitraUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDigitraUser extends ViewRecord
{
    protected static string $resource = DigitraUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones de editar/eliminar (solo lectura)
        ];
    }
}
