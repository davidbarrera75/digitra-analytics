<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservaResource\Pages;
use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ReservaResource extends Resource
{
    protected static ?string $model = Reserva::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Reservas';
    protected static ?string $modelLabel = 'Reserva';
    protected static ?string $pluralModelLabel = 'Reservas';
    protected static ?string $navigationGroup = 'Datos de Digitra';
    protected static ?int $navigationSort = 3;

    /**
     * Filtrar query por tenant (solo reservas de establecimientos del tenant)
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Si es super admin sin tenant activo, mostrar todo
        if (auth()->user()->isSuperAdmin() && !session()->has('active_tenant_id')) {
            return $query;
        }

        // Filtrar por establecimientos del tenant actual
        if ($digitraUserId = digitra_user_id()) {
            return $query->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                $q->where('user_id', $digitraUserId);
            });
        }

        // Si no hay tenant, no mostrar nada
        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('establecimiento.nombre')
                    ->label('Propiedad')
                    ->sortable()
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check-in')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check-out')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('noches')
                    ->label('Noches')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('numero_acompanantes')
                    ->label('Acompañantes')
                    ->sortable(),

                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Finalizada' => 'gray',
                        'Futura' => 'info',
                        'En curso' => 'success',
                        default => 'warning',
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('establecimiento_id')
                    ->label('Propiedad')
                    ->options(function () {
                        $digitraUserId = digitra_user_id();

                        if (!$digitraUserId) {
                            return [];
                        }

                        return Establecimiento::where('user_id', $digitraUserId)
                            ->where('deleted', false)
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('check_in')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Check-in desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Check-in hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn ($q, $date) => $q->whereDate('check_in', '>=', $date))
                            ->when($data['hasta'], fn ($q, $date) => $q->whereDate('check_in', '<=', $date));
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('check_in', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información de la Reserva')
                    ->schema([
                        Components\TextEntry::make('id')->label('ID'),
                        Components\TextEntry::make('establecimiento.nombre')->label('Propiedad'),
                        Components\TextEntry::make('check_in')->label('Check-in')->date('d/m/Y'),
                        Components\TextEntry::make('check_out')->label('Check-out')->date('d/m/Y'),
                        Components\TextEntry::make('noches')->label('Noches')->badge(),
                        Components\TextEntry::make('numero_acompanantes')->label('Acompañantes'),
                        Components\TextEntry::make('motivo')->label('Motivo'),
                        Components\TextEntry::make('tipo_acomodacion')->label('Acomodación'),
                    ])->columns(2),

                Components\Section::make('Información Financiera')
                    ->schema([
                        Components\TextEntry::make('precio')->label('Precio')->money('COP'),
                        Components\IconEntry::make('seguro')->label('Seguro')->boolean(),
                    ])->columns(2),

                Components\Section::make('Contacto')
                    ->schema([
                        Components\TextEntry::make('email')->label('Email'),
                        Components\TextEntry::make('telefono')->label('Teléfono'),
                        Components\TextEntry::make('pais_procedencia')->label('País Procedencia'),
                    ])->columns(2),

                Components\Section::make('Estado')
                    ->schema([
                        Components\TextEntry::make('estado')->label('Estado')->badge(),
                        Components\IconEntry::make('is_active')->label('Activa')->boolean(),
                        Components\IconEntry::make('tra_send')->label('TRA Enviado')->boolean(),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservas::route('/'),
            'view' => Pages\ViewReserva::route('/{record}'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
