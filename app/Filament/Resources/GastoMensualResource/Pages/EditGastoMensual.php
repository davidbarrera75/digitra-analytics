<?php

namespace App\Filament\Resources\GastoMensualResource\Pages;

use App\Filament\Resources\GastoMensualResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGastoMensual extends EditRecord
{
    protected static string $resource = GastoMensualResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
