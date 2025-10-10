<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GastoMensualResource\Pages;
use App\Models\Digitra\Establecimiento;
use App\Models\GastoMensual;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GastoMensualResource extends Resource
{
    protected static ?string $model = GastoMensual::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Gastos Mensuales';

    protected static ?string $modelLabel = 'Gasto Mensual';

    protected static ?string $pluralModelLabel = 'Gastos Mensuales';

    protected static ?string $navigationGroup = 'Informes';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Gasto')
                    ->description('Registra los gastos operacionales mensuales de la propiedad')
                    ->schema([
                        Forms\Components\Select::make('establecimiento_id')
                            ->label('Propiedad')
                            ->options(function () {
                                $digitraUserId = digitra_user_id();
                                return Establecimiento::where('user_id', $digitraUserId)
                                    ->where('deleted', false)
                                    ->pluck('nombre', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->columnSpan(2),

                        Forms\Components\Select::make('mes')
                            ->label('Mes')
                            ->options([
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ])
                            ->required()
                            ->native(false)
                            ->default(now()->month),

                        Forms\Components\TextInput::make('año')
                            ->label('Año')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2099)
                            ->default(now()->year),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detalle de Gastos')
                    ->description('Ingresa los montos de cada tipo de gasto (en pesos colombianos)')
                    ->schema([
                        Forms\Components\TextInput::make('aseo')
                            ->label('Valor Aseo')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Valor pagado por servicios de aseo'),

                        Forms\Components\TextInput::make('administracion')
                            ->label('Administración Edificio')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Cuota de administración del edificio/conjunto'),

                        Forms\Components\TextInput::make('otros_gastos')
                            ->label('Otros Gastos')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->helperText('Cualquier otro gasto operacional'),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notas')
                            ->label('Notas Adicionales')
                            ->rows(3)
                            ->placeholder('Ej: Reparación de aire acondicionado, mantenimiento preventivo, etc.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('establecimiento.nombre')
                    ->label('Propiedad')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('periodo')
                    ->label('Período')
                    ->sortable(['año', 'mes'])
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('aseo')
                    ->label('Aseo')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('administracion')
                    ->label('Administración')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('otros_gastos')
                    ->label('Otros')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_gastos')
                    ->label('Total')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('notas')
                    ->label('Notas')
                    ->limit(30)
                    ->toggleable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('establecimiento_id')
                    ->label('Propiedad')
                    ->options(function () {
                        $digitraUserId = digitra_user_id();
                        return Establecimiento::where('user_id', $digitraUserId)
                            ->where('deleted', false)
                            ->pluck('nombre', 'id');
                    })
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('año')
                    ->label('Año')
                    ->options(function () {
                        $añoActual = now()->year;
                        return collect(range($añoActual - 5, $añoActual + 1))
                            ->mapWithKeys(fn ($año) => [$año => $año]);
                    }),

                Tables\Filters\SelectFilter::make('mes')
                    ->label('Mes')
                    ->options([
                        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('año', 'desc')
            ->defaultSort('mes', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Filtrar por tenant (solo ver gastos de sus propiedades)
        // Usamos whereIn en lugar de whereHas para evitar JOINs cross-database
        $digitraUserId = digitra_user_id();
        if ($digitraUserId) {
            $establecimientoIds = Establecimiento::where('user_id', $digitraUserId)
                ->where('deleted', false)
                ->pluck('id');

            $query->whereIn('establecimiento_id', $establecimientoIds);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGastoMensuals::route('/'),
            'create' => Pages\CreateGastoMensual::route('/create'),
            'edit' => Pages\EditGastoMensual::route('/{record}/edit'),
        ];
    }
}
