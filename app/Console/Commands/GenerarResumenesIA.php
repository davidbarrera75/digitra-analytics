<?php

namespace App\Console\Commands;

use App\Jobs\GenerarResumenMensualIA;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerarResumenesIA extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resumen:generar
                            {--user= : ID del usuario específico (opcional)}
                            {--mes= : Mes a generar (1-12), por defecto mes anterior}
                            {--año= : Año a generar, por defecto año actual}
                            {--todos : Generar para todos los usuarios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar resúmenes mensuales con IA para usuarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mes = $this->option('mes') ?? now()->subMonth()->month;
        $año = $this->option('año') ?? now()->year;

        $this->info("🤖 Generando resúmenes para {$mes}/{$año}");

        if ($this->option('user')) {
            // Generar para un usuario específico
            $user = User::find($this->option('user'));

            if (!$user) {
                $this->error("Usuario no encontrado");
                return 1;
            }

            $this->generarParaUsuario($user, $mes, $año);

        } elseif ($this->option('todos')) {
            // Generar para todos los usuarios que tengan tenant
            $usuarios = User::whereHas('tenant')->get();

            $this->info("Generando resúmenes para {$usuarios->count()} usuarios...");

            $bar = $this->output->createProgressBar($usuarios->count());
            $bar->start();

            foreach ($usuarios as $user) {
                $this->generarParaUsuario($user, $mes, $año);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("✅ Proceso completado");

        } else {
            $this->error("Debes especificar --user=ID o --todos");
            return 1;
        }

        return 0;
    }

    /**
     * Generar resumen para un usuario específico
     */
    private function generarParaUsuario(User $user, int $mes, int $año): void
    {
        try {
            $this->line("Generando para: {$user->name} (ID: {$user->id})");

            // Despachar el job
            GenerarResumenMensualIA::dispatch($user, $mes, $año);

            $this->info("✓ Job despachado para {$user->name}");

        } catch (\Exception $e) {
            $this->error("✗ Error para {$user->name}: " . $e->getMessage());
        }
    }
}
