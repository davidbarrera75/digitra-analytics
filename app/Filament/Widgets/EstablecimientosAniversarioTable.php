<?php

namespace App\Filament\Widgets;

use App\Models\Digitra\Establecimiento;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class EstablecimientosAniversarioTable extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    /**
     * Visible para todos los usuarios autenticados
     */
    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * Título dinámico según tipo de usuario
     */
    protected function getTableHeading(): ?string
    {
        $user = auth()->user();

        if ($user && $user->isSuperAdmin()) {
            return '🎂 Establecimientos que Cumplen 1 Año';
        }

        return '📅 Establecimientos que Cumplen 1 Año (Vencimientos)';
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        $digitraUserId = digitra_user_id();

        $query = Establecimiento::query()
            ->activos()
            ->with(['user'])
            ->withCount('reservas')
            ->proximosAniversarios(30) // Próximos 30 días
            ->orderByRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) ASC');

        // Solo filtrar por usuario si NO es super admin
        if (!$isSuperAdmin && $digitraUserId) {
            $query->where('user_id', $digitraUserId);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Establecimiento')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-office-2'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Propietario')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_aniversario')
                    ->label($isSuperAdmin ? 'Aniversario (1 Año)' : 'Vencimiento (1 Año)')
                    ->getStateUsing(function ($record) {
                        return $record->fecha_aniversario?->format('d/m/Y');
                    })
                    ->badge()
                    ->color('success')
                    ->icon($isSuperAdmin ? 'heroicon-m-cake' : 'heroicon-m-calendar')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("DATE_ADD(created_at, INTERVAL 1 YEAR) {$direction}");
                    }),

                Tables\Columns\TextColumn::make('dias_para_aniversario')
                    ->label($isSuperAdmin ? 'Días para Aniversario' : 'Días para Vencimiento')
                    ->getStateUsing(function ($record) use ($isSuperAdmin) {
                        $dias = $record->dias_para_aniversario;

                        if ($dias === null) {
                            return 'N/A';
                        }

                        if ($dias == 0) {
                            return $isSuperAdmin ? '¡Hoy! 🎉' : '¡Hoy! 📅';
                        } elseif ($dias < 0) {
                            return 'Ya cumplió (' . abs($dias) . ' días atrás)';
                        } else {
                            return $dias . ' días';
                        }
                    })
                    ->badge()
                    ->color(fn ($record) => match(true) {
                        $record->dias_para_aniversario === null => 'gray',
                        $record->dias_para_aniversario == 0 => 'success',
                        $record->dias_para_aniversario < 0 => 'gray',
                        $record->dias_para_aniversario <= 7 => 'warning',
                        default => 'primary',
                    }),

                Tables\Columns\TextColumn::make('reservas_count')
                    ->label('Total Reservas')
                    ->counts('reservas')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->getStateUsing(function ($record) {
                        return $record->indicativo && $record->telefono
                            ? '+' . $record->indicativo . ' ' . $record->telefono
                            : 'N/A';
                    })
                    ->icon('heroicon-m-phone'),
            ])
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) ASC'))
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s'); // Actualizar cada 60 segundos
    }
}
