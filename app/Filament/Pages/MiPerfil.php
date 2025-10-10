<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class MiPerfil extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Mi Perfil';
    protected static ?string $title = '👤 Mi Perfil';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.mi-perfil';

    // Datos del formulario
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Section::make('Información de la Cuenta')
                    ->description('Información básica de tu cuenta')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->default($user->name)
                            ->disabled(),

                        TextInput::make('email')
                            ->label('Email')
                            ->default($user->email)
                            ->disabled(),

                        TextInput::make('tenant')
                            ->label('Organización')
                            ->default($user->tenant?->name ?? 'N/A')
                            ->disabled()
                            ->visible(!$user->isSuperAdmin()),
                    ])
                    ->columns(2),

                Section::make('Cambiar Contraseña')
                    ->description('Actualiza tu contraseña para mayor seguridad')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Contraseña Actual')
                            ->password()
                            ->required()
                            ->revealable()
                            ->currentPassword(),

                        TextInput::make('new_password')
                            ->label('Nueva Contraseña')
                            ->password()
                            ->required()
                            ->revealable()
                            ->minLength(8)
                            ->same('new_password_confirmation')
                            ->helperText('Mínimo 8 caracteres'),

                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar Nueva Contraseña')
                            ->password()
                            ->required()
                            ->revealable()
                            ->dehydrated(false),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function cambiarPassword(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();

        // Verificar contraseña actual
        if (!Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Error')
                ->body('La contraseña actual es incorrecta.')
                ->danger()
                ->send();

            return;
        }

        // Actualizar contraseña
        $user->password = Hash::make($data['new_password']);
        $user->save();

        // Limpiar formulario
        $this->form->fill();

        Notification::make()
            ->title('Contraseña Actualizada')
            ->body('Tu contraseña ha sido cambiada exitosamente.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('cambiar_password')
                ->label('Cambiar Contraseña')
                ->icon('heroicon-o-key')
                ->color('primary')
                ->action('cambiarPassword'),
        ];
    }
}
