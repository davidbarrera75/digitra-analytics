<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EstablecimientoResource\Pages;
use App\Models\Digitra\Establecimiento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class EstablecimientoResource extends Resource
{
    protected static ?string $model = Establecimiento::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Propiedades';
    protected static ?string $modelLabel = 'Propiedad';
    protected static ?string $pluralModelLabel = 'Propiedades';
    protected static ?string $navigationGroup = 'Datos de Digitra';
    protected static ?int $navigationSort = 2;

    /**
     * Filtrar query por tenant
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Si es super admin sin tenant activo, mostrar todo
        if (auth()->user()->isSuperAdmin() && !session()->has('active_tenant_id')) {
            return $query;
        }

        // Filtrar por el digitra_user_id del tenant actual
        if ($digitraUserId = digitra_user_id()) {
            return $query->where('user_id', $digitraUserId);
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

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propietario')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('rnt')
                    ->label('RNT')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('nit')
                    ->label('NIT')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reservas_count')
                    ->label('Reservas')
                    ->counts('reservas')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\IconColumn::make('auto_send_tra')
                    ->label('Auto TRA')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('deleted')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Propietario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('deleted')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Eliminados')
                    ->falseLabel('Activos'),

                Tables\Filters\TernaryFilter::make('auto_send_tra')
                    ->label('Auto TRA')
                    ->placeholder('Todos')
                    ->trueLabel('Con Auto TRA')
                    ->falseLabel('Sin Auto TRA'),

                Tables\Filters\Filter::make('con_reservas')
                    ->label('Con Reservas')
                    ->query(fn ($query) => $query->has('reservas')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información General')
                    ->schema([
                        Components\TextEntry::make('id')->label('ID'),
                        Components\TextEntry::make('nombre')->label('Nombre'),
                        Components\TextEntry::make('user.name')->label('Propietario'),
                        Components\TextEntry::make('rnt')->label('RNT')->badge()->color('info'),
                        Components\TextEntry::make('nit')->label('NIT'),
                        Components\TextEntry::make('codigo_dane')->label('Código DANE'),
                    ])->columns(2),

                Components\Section::make('Contacto')
                    ->schema([
                        Components\TextEntry::make('direccion')->label('Dirección'),
                        Components\TextEntry::make('telefono')->label('Teléfono'),
                        Components\TextEntry::make('indicativo')->label('Indicativo'),
                        Components\TextEntry::make('emails')
                            ->label('Emails')
                            ->badge(),
                    ])->columns(2),

                Components\Section::make('Configuración')
                    ->schema([
                        Components\IconEntry::make('auto_send_tra')
                            ->label('Auto Envío TRA')
                            ->boolean(),
                        Components\IconEntry::make('documentacion')
                            ->label('Documentación')
                            ->boolean(),
                        Components\IconEntry::make('deleted')
                            ->label('Eliminado')
                            ->boolean(),
                        Components\TextEntry::make('short_url')->label('URL Corta'),
                    ])->columns(2),

                Components\Section::make('Estadísticas')
                    ->schema([
                        Components\TextEntry::make('reservas_count')
                            ->label('Total Reservas')
                            ->state(fn ($record) => $record->reservas()->count())
                            ->badge()
                            ->color('success'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEstablecimientos::route('/'),
            'view' => Pages\ViewEstablecimiento::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
