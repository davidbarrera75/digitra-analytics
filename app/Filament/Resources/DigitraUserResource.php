<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DigitraUserResource\Pages;
use App\Models\Digitra\User as DigitraUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class DigitraUserResource extends Resource
{
    protected static ?string $model = DigitraUser::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios Digitra';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Datos de Digitra';
    protected static ?int $navigationSort = 1;

    /**
     * Solo visible para super admins
     */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        // Formulario de solo lectura
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Usuario')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('legal_representant')
                            ->label('Representante Legal')
                            ->disabled(),
                        Forms\Components\TextInput::make('legal_representant_document')
                            ->label('Documento Representante')
                            ->disabled(),
                        Forms\Components\Toggle::make('is_colasistencia')
                            ->label('Es Colasistencia')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('establecimientos_count')
                    ->label('Propiedades')
                    ->counts('establecimientos')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('reservas_count')
                    ->label('Total Reservas')
                    ->counts('reservas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado_actividad')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $ultimaReserva = $record->reservas()
                            ->orderBy('check_in', 'desc')
                            ->first();

                        if (!$ultimaReserva) {
                            return 'SIN ACTIVIDAD';
                        }

                        $diasSinActividad = now()->diffInDays($ultimaReserva->check_in);

                        if ($diasSinActividad <= 30) {
                            return 'ACTIVO';
                        } elseif ($diasSinActividad <= 90) {
                            return 'EN RIESGO';
                        } elseif ($diasSinActividad <= 180) {
                            return 'ALTO RIESGO';
                        } else {
                            return 'INACTIVO';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVO' => 'success',
                        'EN RIESGO' => 'warning',
                        'ALTO RIESGO' => 'danger',
                        'INACTIVO' => 'gray',
                        'SIN ACTIVIDAD' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'ACTIVO' => 'heroicon-m-check-circle',
                        'EN RIESGO' => 'heroicon-m-exclamation-triangle',
                        'ALTO RIESGO' => 'heroicon-m-exclamation-circle',
                        'INACTIVO' => 'heroicon-m-x-circle',
                        'SIN ACTIVIDAD' => 'heroicon-m-minus-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withMax('reservas', 'check_in')
                            ->orderBy('reservas_max_check_in', $direction);
                    }),

                Tables\Columns\TextColumn::make('ultima_reserva')
                    ->label('Última Reserva')
                    ->getStateUsing(function ($record) {
                        $ultimaReserva = $record->reservas()
                            ->orderBy('check_in', 'desc')
                            ->first();

                        return $ultimaReserva ? $ultimaReserva->check_in->format('d/m/Y') : 'Nunca';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withMax('reservas', 'check_in')
                            ->orderBy('reservas_max_check_in', $direction);
                    }),

                Tables\Columns\TextColumn::make('dias_sin_actividad')
                    ->label('Días Sin Actividad')
                    ->getStateUsing(function ($record) {
                        $ultimaReserva = $record->reservas()
                            ->orderBy('check_in', 'desc')
                            ->first();

                        if (!$ultimaReserva) {
                            return 'N/A';
                        }

                        $dias = now()->diffInDays($ultimaReserva->check_in);
                        return $dias . ' días';
                    })
                    ->color(function ($record) {
                        $ultimaReserva = $record->reservas()
                            ->orderBy('check_in', 'desc')
                            ->first();

                        if (!$ultimaReserva) return 'gray';

                        $dias = now()->diffInDays($ultimaReserva->check_in);

                        if ($dias <= 30) return 'success';
                        if ($dias <= 90) return 'warning';
                        if ($dias <= 180) return 'danger';
                        return 'gray';
                    })
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withMax('reservas', 'check_in')
                            ->orderBy('reservas_max_check_in', $direction);
                    }),

                Tables\Columns\TextColumn::make('reservas_30d')
                    ->label('Últimos 30d')
                    ->getStateUsing(function ($record) {
                        return $record->reservas()
                            ->where('check_in', '>=', now()->subDays(30))
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reservas_90d')
                    ->label('Últimos 90d')
                    ->getStateUsing(function ($record) {
                        return $record->reservas()
                            ->where('check_in', '>=', now()->subDays(90))
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reservas_180d')
                    ->label('Últimos 180d')
                    ->getStateUsing(function ($record) {
                        return $record->reservas()
                            ->where('check_in', '>=', now()->subDays(180))
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reservas_365d')
                    ->label('Último Año')
                    ->getStateUsing(function ($record) {
                        return $record->reservas()
                            ->where('check_in', '>=', now()->subDays(365))
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_colasistencia')
                    ->label('Colasistencia')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado_actividad')
                    ->label('Estado de Actividad')
                    ->options([
                        'activo' => '🟢 Activo (≤30 días)',
                        'en_riesgo' => '🟡 En Riesgo (31-90 días)',
                        'alto_riesgo' => '🟠 Alto Riesgo (91-180 días)',
                        'inactivo' => '🔴 Inactivo (>180 días)',
                        'sin_actividad' => '⚫ Sin Actividad',
                    ])
                    ->query(function ($query, array $data) {
                        if (!$data['value']) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'activo' => $query->whereHas('reservas', function ($q) {
                                $q->where('check_in', '>=', now()->subDays(30));
                            }),
                            'en_riesgo' => $query->whereHas('reservas', function ($q) {
                                $q->where('check_in', '<', now()->subDays(30))
                                  ->where('check_in', '>=', now()->subDays(90));
                            }),
                            'alto_riesgo' => $query->whereHas('reservas', function ($q) {
                                $q->where('check_in', '<', now()->subDays(90))
                                  ->where('check_in', '>=', now()->subDays(180));
                            }),
                            'inactivo' => $query->whereHas('reservas', function ($q) {
                                $q->where('check_in', '<', now()->subDays(180));
                            }),
                            'sin_actividad' => $query->doesntHave('reservas'),
                            default => $query,
                        };
                    }),

                Tables\Filters\Filter::make('sin_reservas_30d')
                    ->label('Sin reservas últimos 30 días')
                    ->query(function ($query) {
                        return $query->whereDoesntHave('reservas', function ($q) {
                            $q->where('check_in', '>=', now()->subDays(30));
                        });
                    })
                    ->toggle(),

                Tables\Filters\Filter::make('sin_reservas_90d')
                    ->label('Sin reservas últimos 90 días')
                    ->query(function ($query) {
                        return $query->whereDoesntHave('reservas', function ($q) {
                            $q->where('check_in', '>=', now()->subDays(90));
                        });
                    })
                    ->toggle(),

                Tables\Filters\Filter::make('sin_reservas_180d')
                    ->label('Sin reservas últimos 6 meses')
                    ->query(function ($query) {
                        return $query->whereDoesntHave('reservas', function ($q) {
                            $q->where('check_in', '>=', now()->subDays(180));
                        });
                    })
                    ->toggle(),

                Tables\Filters\Filter::make('sin_reservas_365d')
                    ->label('Sin reservas último año')
                    ->query(function ($query) {
                        return $query->whereDoesntHave('reservas', function ($q) {
                            $q->where('check_in', '>=', now()->subDays(365));
                        });
                    })
                    ->toggle(),

                Tables\Filters\TernaryFilter::make('is_colasistencia')
                    ->label('Colasistencia')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Colasistencia')
                    ->falseLabel('Sin Colasistencia'),

                Tables\Filters\Filter::make('con_propiedades')
                    ->label('Con Propiedades')
                    ->query(fn ($query) => $query->has('establecimientos'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort(function ($query) {
                return $query->withMax('reservas', 'check_in')
                    ->orderByRaw('COALESCE(reservas_max_check_in, "1900-01-01") DESC');
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información General')
                    ->schema([
                        Components\TextEntry::make('id')->label('ID'),
                        Components\TextEntry::make('name')->label('Nombre'),
                        Components\TextEntry::make('email')->label('Email'),
                        Components\TextEntry::make('legal_representant')->label('Representante Legal'),
                        Components\TextEntry::make('legal_representant_document')->label('Documento'),
                        Components\IconEntry::make('is_colasistencia')
                            ->label('Colasistencia')
                            ->boolean(),
                    ])->columns(2),

                Components\Section::make('Estadísticas')
                    ->schema([
                        Components\TextEntry::make('establecimientos_count')
                            ->label('Total Propiedades')
                            ->state(fn ($record) => $record->establecimientos()->count()),
                        Components\TextEntry::make('reservas_count')
                            ->label('Total Reservas')
                            ->state(fn ($record) => $record->reservas()->count()),
                    ])->columns(2),

                Components\Section::make('Fechas')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i:s'),
                        Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i:s'),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDigitraUsers::route('/'),
            'view' => Pages\ViewDigitraUser::route('/{record}'),
        ];
    }

    // Deshabilitar creación y edición (solo lectura)
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

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
