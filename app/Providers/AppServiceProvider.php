<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Digitra\User as DigitraUser;
use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use App\Models\Digitra\Huesped;
use App\Observers\ReadOnlyDigitraObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 🔒 SEGURIDAD: Registrar Observer de Solo Lectura para TODOS los modelos de Digitra
        // Esto previene cualquier operación de escritura en la BD de producción
        DigitraUser::observe(ReadOnlyDigitraObserver::class);
        Establecimiento::observe(ReadOnlyDigitraObserver::class);
        Reserva::observe(ReadOnlyDigitraObserver::class);
        Huesped::observe(ReadOnlyDigitraObserver::class);
    }
}
