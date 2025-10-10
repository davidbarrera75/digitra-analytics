<?php

namespace App\Filament\Resources\GastoMensualResource\Pages;

use App\Filament\Resources\GastoMensualResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGastoMensuals extends ListRecords
{
    protected static string $resource = GastoMensualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
