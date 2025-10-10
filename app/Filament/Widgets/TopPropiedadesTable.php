<?php

namespace App\Filament\Widgets;

use App\Models\Digitra\Establecimiento;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class TopPropiedadesTable extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    /**
     * Solo visible para super admins
     */
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        // Cachear IDs de las top propiedades por 10 minutos
        $topPropiedadesIds = Cache::remember('digitra_top_propiedades_ids', 600, function () {
            return Establecimiento::query()
                ->activos()
                ->withCount('reservas')
                ->orderByDesc('reservas_count')
                ->limit(10)
                ->pluck('id')
                ->toArray();
        });

        return $table
            ->heading('Top 10 Propiedades por Reservas')
            ->query(
                Establecimiento::query()
                    ->whereIn('id', $topPropiedadesIds)
                    ->withCount('reservas')
                    ->orderByDesc('reservas_count')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Propiedad')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propietario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('rnt')
                    ->label('RNT')
                    ->searchable(),

                Tables\Columns\TextColumn::make('reservas_count')
                    ->label('Total Reservas')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->getStateUsing(fn ($record) => $record->codigo_dane ?? 'N/A'),

                Tables\Columns\IconColumn::make('auto_send_tra')
                    ->label('Auto TRA')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('reservas_count', 'desc');
    }
}
