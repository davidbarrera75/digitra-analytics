<?php
namespace App\Filament\Resources;
use App\Filament\Resources\HuespedResource\Pages;
use App\Models\Digitra\Huesped;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
class HuespedResource extends Resource {
    protected static ?string $model = Huesped::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Huéspedes';
    protected static ?string $modelLabel = 'Huésped';
    protected static ?string $pluralModelLabel = 'Huéspedes';
    protected static ?string $navigationGroup = 'Datos de Digitra';
    protected static ?int $navigationSort = 4;

    /**
     * Filtrar query por tenant (solo huéspedes de reservas del tenant)
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Si es super admin sin tenant activo, mostrar todo
        if (auth()->user()->isSuperAdmin() && !session()->has('active_tenant_id')) {
            return $query;
        }

        // Filtrar por reservas de establecimientos del tenant
        if ($digitraUserId = digitra_user_id()) {
            return $query->whereHas('reserva.establecimiento', function ($q) use ($digitraUserId) {
                $q->where('user_id', $digitraUserId);
            });
        }

        // Si no hay tenant, no mostrar nada
        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('nombre_completo')->label('Nombre')->searchable(['nombres', 'apellidos'])->weight('bold'),
            Tables\Columns\TextColumn::make('tipo_documento')->label('Tipo Doc')->searchable(),
            Tables\Columns\TextColumn::make('numero_documento')->label('Documento')->searchable()->copyable(),
            Tables\Columns\TextColumn::make('nacionalidad')->label('Nacionalidad')->searchable(),
            Tables\Columns\TextColumn::make('reserva.establecimiento.nombre')->label('Propiedad')->limit(30)->toggleable(),
            Tables\Columns\IconColumn::make('principal')->label('Principal')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->label('Registro')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\TernaryFilter::make('principal')->label('Principal')->placeholder('Todos')->trueLabel('Principales')->falseLabel('Acompañantes'),
        ])->actions([Tables\Actions\ViewAction::make()])->defaultSort('id', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Información Personal')
                    ->schema([
                        Components\TextEntry::make('id')->label('ID'),
                        Components\TextEntry::make('nombre_completo')->label('Nombre Completo'),
                        Components\TextEntry::make('tipo_documento')->label('Tipo de Documento'),
                        Components\TextEntry::make('numero_documento')->label('Número de Documento'),
                        Components\TextEntry::make('nacionalidad')->label('Nacionalidad'),
                        Components\TextEntry::make('sexo')->label('Sexo'),
                        Components\TextEntry::make('fecha_nacimiento')
                            ->label('Fecha de Nacimiento')
                            ->date('d/m/Y'),
                    ])->columns(2),

                Components\Section::make('Información de Contacto')
                    ->schema([
                        Components\TextEntry::make('email')->label('Email'),
                        Components\TextEntry::make('telefono')->label('Teléfono'),
                    ])->columns(2),

                Components\Section::make('Información de la Reserva')
                    ->schema([
                        Components\TextEntry::make('reserva.id')->label('ID Reserva'),
                        Components\TextEntry::make('reserva.establecimiento.nombre')->label('Propiedad'),
                        Components\TextEntry::make('reserva.check_in')
                            ->label('Check-In')
                            ->date('d/m/Y'),
                        Components\TextEntry::make('reserva.check_out')
                            ->label('Check-Out')
                            ->date('d/m/Y'),
                        Components\IconEntry::make('principal')
                            ->label('Huésped Principal')
                            ->boolean(),
                    ])->columns(2),

                Components\Section::make('Fechas de Registro')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Fecha de Creación')
                            ->dateTime('d/m/Y H:i:s'),
                        Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/m/Y H:i:s'),
                    ])->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListHuespedes::route('/'),
            'view' => Pages\ViewHuesped::route('/{record}'),
        ];
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
}
