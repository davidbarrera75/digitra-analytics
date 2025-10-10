<?php

namespace App\Jobs;

use App\Models\ResumenMensualIA;
use App\Models\User;
use App\Models\Digitra\Reserva;
use App\Models\GastoMensual;
use App\Services\ClaudeService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerarResumenMensualIA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 minutos de timeout

    private User $user;
    private int $mes;
    private int $año;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, int $mes, int $año)
    {
        $this->user = $user;
        $this->mes = $mes;
        $this->año = $año;
    }

    /**
     * Execute the job.
     */
    public function handle(ClaudeService $claudeService): void
    {
        try {
            // Verificar si ya existe un resumen para este período
            if (ResumenMensualIA::existeResumen($this->user->id, $this->mes, $this->año)) {
                Log::info("Resumen ya existe para usuario {$this->user->id} - {$this->mes}/{$this->año}");
                return;
            }

            // Recopilar datos del usuario
            $datos = $this->recopilarDatos();

            // Verificar si el usuario tiene datos suficientes
            if ($datos['estadisticas']['reservas_count'] === 0) {
                Log::info("Usuario {$this->user->id} no tiene reservas en {$this->mes}/{$this->año}, saltando resumen");
                return;
            }

            // Generar resumen con Claude
            $resultado = $claudeService->generarResumenMensual($datos);

            // Guardar en la base de datos
            ResumenMensualIA::create([
                'user_id' => $this->user->id,
                'mes' => $this->mes,
                'año' => $this->año,
                'contenido' => $resultado['contenido'],
                'datos_estadisticos' => $datos,
                'tokens_usados' => $resultado['tokens'],
                'generado_en' => now(),
            ]);

            Log::info("Resumen generado exitosamente para usuario {$this->user->id} - {$this->mes}/{$this->año}");

        } catch (\Exception $e) {
            Log::error("Error generando resumen para usuario {$this->user->id}", [
                'mes' => $this->mes,
                'año' => $this->año,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Recopilar todos los datos necesarios para el resumen
     */
    private function recopilarDatos(): array
    {
        $inicio = Carbon::create($this->año, $this->mes, 1)->startOfMonth();
        $fin = $inicio->copy()->endOfMonth();

        // Mes anterior para comparación
        $inicioAnterior = $inicio->copy()->subMonth()->startOfMonth();
        $finAnterior = $inicioAnterior->copy()->endOfMonth();

        // Obtener tenant del usuario
        $tenant = $this->user->tenant;
        $digitraUserId = $tenant?->digitra_user_id;

        if (!$digitraUserId) {
            throw new \Exception("Usuario no tiene tenant configurado");
        }

        // Recopilar estadísticas del mes
        $estadisticas = $this->calcularEstadisticas($digitraUserId, $inicio, $fin);

        // Recopilar estadísticas del mes anterior
        $estadisticasAnterior = $this->calcularEstadisticas($digitraUserId, $inicioAnterior, $finAnterior);

        // Obtener propiedades del usuario
        $propiedades = \App\Models\Digitra\Establecimiento::where('user_id', $digitraUserId)
            ->where('deleted', false)
            ->get(['id', 'nombre'])
            ->map(function ($propiedad) {
                return [
                    'id' => $propiedad->id,
                    'nombre' => $propiedad->nombre,
                ];
            })
            ->toArray();

        return [
            'mes' => $this->mes,
            'año' => $this->año,
            'nombre_mes' => $this->getNombreMes($this->mes),
            'usuario' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'estadisticas' => $estadisticas,
            'mes_anterior' => $estadisticasAnterior,
            'propiedades' => $propiedades,
        ];
    }

    /**
     * Calcular estadísticas para un período
     */
    private function calcularEstadisticas($digitraUserId, Carbon $inicio, Carbon $fin): array
    {
        // Obtener reservas del período
        $reservas = Reserva::whereHas('establecimiento', function ($q) use ($digitraUserId) {
            $q->where('user_id', $digitraUserId);
        })
        ->whereBetween('check_in', [$inicio, $fin])
        ->get();

        $ingresos = $reservas->sum('precio');
        $reservasCount = $reservas->count();
        $noches = $reservas->sum('noches');
        $precioPromedio = $reservasCount > 0 ? $ingresos / $noches : 0;

        // Calcular porcentaje de ocupación (asumiendo 30 días por mes y N propiedades)
        $propiedadesCount = \App\Models\Digitra\Establecimiento::where('user_id', $digitraUserId)
            ->where('deleted', false)
            ->count();

        $diasDisponibles = $propiedadesCount * $inicio->daysInMonth;
        $porcentajeOcupacion = $diasDisponibles > 0 ? ($noches / $diasDisponibles) * 100 : 0;

        // Obtener IDs de establecimientos del usuario (MySQL)
        $establecimientosIds = \App\Models\Digitra\Establecimiento::where('user_id', $digitraUserId)
            ->where('deleted', false)
            ->pluck('id')
            ->toArray();

        // Obtener gastos del período (SQLite) - Usar DB::table para evitar relaciones Eloquent
        $gastosTotal = 0;
        if (count($establecimientosIds) > 0) {
            $gastosTotal = \DB::table('gastos_mensuales')
                ->whereIn('establecimiento_id', $establecimientosIds)
                ->where('mes', $inicio->month)
                ->where('año', $inicio->year)
                ->get()
                ->sum(function ($gasto) {
                    return $gasto->aseo + $gasto->administracion + $gasto->otros_gastos;
                });
        }

        $utilidadNeta = $ingresos - $gastosTotal;

        return [
            'ingresos' => $ingresos,
            'gastos_total' => $gastosTotal,
            'utilidad_neta' => $utilidadNeta,
            'reservas_count' => $reservasCount,
            'noches_totales' => $noches,
            'porcentaje_ocupacion' => $porcentajeOcupacion,
            'precio_promedio_noche' => $precioPromedio,
        ];
    }

    /**
     * Obtener nombre del mes en español
     */
    private function getNombreMes(int $mes): string
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return $meses[$mes] ?? 'Desconocido';
    }
}
