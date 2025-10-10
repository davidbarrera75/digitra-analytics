<?php

namespace App\Filament\Resources\DigitraUserResource\Pages;

use App\Filament\Resources\DigitraUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDigitraUsers extends ListRecords
{
    protected static string $resource = DigitraUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Sin acciones de crear (solo lectura)
        ];
    }
}
