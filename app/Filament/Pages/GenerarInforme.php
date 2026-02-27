<?php

namespace App\Filament\Pages;

use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use App\Models\InformeDescarga;
use App\Models\ReservaCorregida;
use App\Models\ReservaIgnorada;
use App\Services\InformeService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class GenerarInforme extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.generar-informe';

    protected static ?string $navigationLabel = 'Generar Informe';

    protected static ?string $title = '📊 Generar Informe PDF';

    protected static ?string $navigationGroup = 'Informes';

    protected static ?int $navigationSort = 1;

    // Propiedades del formulario
    public ?array $data = [];

    // Propiedades para vista previa
    public $mostrarVistaPrevia = false;
    public $datosInforme = null;
    public $insightsInforme = null;

    // Propiedades para revisión de calidad
    public $mostrarRevisionCalidad = false;
    public $reservasProblematicas = [];
    public $totalProblemas = 0;

    /**
     * Montaje inicial
     */
    public function mount(): void
    {
        // Valores por defecto: últimos 3 meses hasta hoy
        $this->form->fill([
            'fecha_inicio' => now()->subMonths(3)->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
            'establecimiento_id' => '0', // '0' = informe general
        ]);
    }

    /**
     * Definir formulario
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración del Informe')
                    ->description('Selecciona el tipo de informe y el período')
                    ->schema([
                        Select::make('establecimiento_id')
                            ->label('Tipo de Informe')
                            ->options(function () {
                                $user = auth()->user();
                                $tenant = $user?->tenant;
                                $digitraUserId = $tenant?->digitra_user_id;

                                $opciones = [
                                    '0' => '📊 Informe General (Todos mis Establecimientos)',
                                ];

                                if ($digitraUserId) {
                                    $establecimientos = Establecimiento::activos()
                                        ->where('user_id', $digitraUserId)
                                        ->orderBy('nombre')
                                        ->get(['id', 'nombre']);

                                    foreach ($establecimientos as $est) {
                                        $opciones[$est->id] = $est->nombre;
                                    }
                                } elseif ($user?->isSuperAdmin()) {
                                    $establecimientos = Establecimiento::activos()
                                        ->orderBy('nombre')
                                        ->get(['id', 'nombre']);

                                    foreach ($establecimientos as $est) {
                                        $opciones[$est->id] = $est->nombre;
                                    }
                                }

                                return $opciones;
                            })
                            ->placeholder('Selecciona un tipo de informe')
                            ->helperText('Selecciona "Informe General" para todos tus establecimientos, o elige uno específico.')
                            ->searchable()
                            ->native(false)
                            ->default('0'),
                    ])
                    ->collapsible(),

                Section::make('Rango de Fechas')
                    ->description('Selecciona el período para el cual deseas generar el informe')
                    ->schema([
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->default(now()->subMonths(3)->startOfMonth())
                            ->maxDate(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        DatePicker::make('fecha_fin')
                            ->label('Fecha de Fin')
                            ->required()
                            ->default(now())
                            ->maxDate(now())
                            ->after('fecha_inicio')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    /**
     * Revisar calidad de datos antes de generar el informe
     */
    public function revisarCalidad(): void
    {
        $data = $this->form->getState();

        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = Carbon::parse($data['fecha_fin']);

        // Convertir '0' (string) a null para informe general
        $establecimientoId = $data['establecimiento_id'] ?? '0';
        $establecimientoId = ($establecimientoId === '0' || $establecimientoId === 0) ? null : (int)$establecimientoId;

        // Validar que la fecha de inicio sea menor que la de fin
        if ($fechaInicio->gt($fechaFin)) {
            Notification::make()
                ->title('Error en fechas')
                ->body('La fecha de inicio debe ser anterior a la fecha de fin.')
                ->danger()
                ->send();

            return;
        }

        try {
            // Cargar reservas problemáticas
            $this->reservasProblematicas = $this->obtenerReservasProblematicas($fechaInicio, $fechaFin, $establecimientoId);
            $this->totalProblemas = count($this->reservasProblematicas);
            $this->mostrarRevisionCalidad = true;

            if ($this->totalProblemas > 0) {
                Notification::make()
                    ->title('⚠️ Problemas Detectados')
                    ->body("Se encontraron {$this->totalProblemas} reservas con problemas. Revísalas antes de generar el informe.")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('✅ Sin Problemas')
                    ->body('No se detectaron problemas en las reservas. Puedes generar el informe.')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Obtener reservas con problemas del período
     */
    private function obtenerReservasProblematicas(?Carbon $fechaInicio, ?Carbon $fechaFin, ?int $establecimientoId): array
    {
        // Si se especifica un establecimiento, obtener su digitra_id
        $digitraIdEstablecimiento = null;
        if ($establecimientoId) {
            $establecimiento = \App\Models\Digitra\Establecimiento::find($establecimientoId);
            if ($establecimiento) {
                $digitraIdEstablecimiento = $establecimiento->digitra_id;
            }
        }
        $query = Reserva::with(['establecimiento', 'huespedes'])
            ->whereBetween('check_in', [$fechaInicio, $fechaFin]);

        // Filtrar por establecimiento
        if ($digitraIdEstablecimiento) {
            $query->where('establecimiento_digitra_id', $digitraIdEstablecimiento);
            // VALIDACIÓN DE SEGURIDAD: Ya verificamos que el establecimiento pertenece al usuario arriba
        } elseif ($establecimientoId) {
            // Si se especificó un ID pero no se encontró el establecimiento, no mostrar nada
            $query->whereRaw('1 = 0');
        } else {
            // Filtrar por tenant
            $digitraUserId = digitra_user_id();
            if ($digitraUserId) {
                $query->whereHas('establecimiento', function ($q) use ($digitraUserId) {
                    $q->where('user_id', $digitraUserId);
                });
            }
        }

        // Solo reservas con problemas potenciales
        $query->where(function ($q) {
            $q->where('precio', '<', 100)
              ->orWhere('precio', '>', 10000000);
        });

        $reservas = $query->get();

        // Precargar correcciones e ignoradas en bulk (evita N+1)
        $reservaIds = $reservas->pluck('id')->toArray();
        $correcciones = !empty($reservaIds)
            ? ReservaCorregida::whereIn('reserva_id', $reservaIds)->get()->keyBy('reserva_id')
            : collect();
        $ignoradas = !empty($reservaIds)
            ? ReservaIgnorada::whereIn('reserva_id', $reservaIds)->pluck('reserva_id')->flip()
            : collect();

        return $reservas->map(function ($reserva) use ($correcciones, $ignoradas) {
            $primerHuesped = $reserva->huespedes->first();

            $problemas = [];
            if ($reserva->precio < 100) {
                $problemas[] = 'Precio muy bajo';
            }
            if ($reserva->precio > 10000000) {
                $problemas[] = 'Precio muy alto';
            }

            $correccion = $correcciones->get($reserva->id);
            $ignorada = $ignoradas->has($reserva->id);

            $estado = 'Pendiente';
            $precioMostrar = $reserva->precio;

            if ($ignorada) {
                $estado = 'Ignorada';
            } elseif ($correccion) {
                $estado = 'Corregida';
                $precioMostrar = $correccion->precio_corregido;
            }

            return [
                'id' => $reserva->id,
                'establecimiento' => $reserva->establecimiento ? $reserva->establecimiento->nombre : 'N/A',
                'huesped' => $primerHuesped ? $primerHuesped->nombre_completo : 'N/A',
                'check_in' => $reserva->check_in->format('d/m/Y'),
                'check_out' => $reserva->check_out ? $reserva->check_out->format('d/m/Y') : 'N/A',
                'precio_original' => $reserva->precio,
                'precio_mostrar' => $precioMostrar,
                'problemas' => implode(', ', $problemas),
                'estado' => $estado,
                'correccion' => $correccion,
                'ignorada' => $ignorada,
            ];
        })->toArray();
    }

    /**
     * Generar vista previa
     */
    public function generarVistaPrevia(): void
    {
        $data = $this->form->getState();

        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $fechaFin = Carbon::parse($data['fecha_fin']);

        // Convertir '0' (string) a null para informe general
        $establecimientoId = $data['establecimiento_id'] ?? '0';
        $establecimientoId = ($establecimientoId === '0' || $establecimientoId === 0) ? null : (int)$establecimientoId;

        // Validar que la fecha de inicio sea menor que la de fin
        if ($fechaInicio->gt($fechaFin)) {
            Notification::make()
                ->title('Error en fechas')
                ->body('La fecha de inicio debe ser anterior a la fecha de fin.')
                ->danger()
                ->send();

            return;
        }

        try {
            // 🔒 VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            if ($establecimientoId) {
                $user = auth()->user();
                $digitraUserId = $user->tenant?->digitra_user_id;
                
                // Super admin puede ver todo
                if (!$user->isSuperAdmin()) {
                    $establecimiento = Establecimiento::find($establecimientoId);
                    
                    if (!$establecimiento || $establecimiento->user_id !== $digitraUserId) {
                        Notification::make()
                            ->title('Error de Seguridad')
                            ->body('No tienes permiso para acceder a este establecimiento.')
                            ->danger()
                            ->send();
                        return;
                    }
                }
            }
            
            // Generar datos del informe
            $informeService = new InformeService();
            $this->datosInforme = $informeService->generarDatosInforme($fechaInicio, $fechaFin, $establecimientoId);
            $this->insightsInforme = $informeService->generarInsights($this->datosInforme);

            // Mostrar vista previa
            $this->mostrarVistaPrevia = true;
            $this->mostrarRevisionCalidad = false;

            Notification::make()
                ->title('Vista Previa Generada')
                ->body('Puedes revisar el informe y descargar el PDF.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Corregir precio de una reserva
     */
    public function corregirPrecio($reservaId, $precioCorregido, $motivo = 'valor_atipico', $notas = null): void
    {
        try {
            $reserva = Reserva::find($reservaId);

            if (!$reserva) {
                Notification::make()
                    ->title('Error')
                    ->body('Reserva no encontrada.')
                    ->danger()
                    ->send();
                return;
            }

            // SEGURIDAD: Verificar que la reserva pertenece al tenant actual
            if (!$this->reservaPerteneceAlTenant($reserva)) {
                Notification::make()
                    ->title('Error de Seguridad')
                    ->body('No tienes permiso para modificar esta reserva.')
                    ->danger()
                    ->send();
                return;
            }

            ReservaCorregida::updateOrCreate(
                ['reserva_id' => $reservaId],
                [
                    'precio_original' => $reserva->precio,
                    'precio_corregido' => $precioCorregido,
                    'motivo' => $motivo,
                    'notas' => $notas,
                    'user_id' => digitra_user_id(),
                ]
            );

            // Recargar la lista
            $data = $this->form->getState();
            $fechaInicio = Carbon::parse($data['fecha_inicio']);
            $fechaFin = Carbon::parse($data['fecha_fin']);
            $establecimientoId = $data['establecimiento_id'] ?? '0';
            $establecimientoId = ($establecimientoId === '0' || $establecimientoId === 0) ? null : (int)$establecimientoId;

            $this->reservasProblematicas = $this->obtenerReservasProblematicas($fechaInicio, $fechaFin, $establecimientoId);
            $this->totalProblemas = count($this->reservasProblematicas);

            Notification::make()
                ->title('✅ Precio Corregido')
                ->body("El precio de la reserva #{$reservaId} ha sido actualizado.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Ignorar una reserva
     */
    public function ignorarReserva($reservaId, $motivo = 'duplicada', $notas = null): void
    {
        try {
            // SEGURIDAD: Verificar que la reserva pertenece al tenant actual
            $reserva = Reserva::find($reservaId);
            if (!$reserva || !$this->reservaPerteneceAlTenant($reserva)) {
                Notification::make()
                    ->title('Error de Seguridad')
                    ->body('No tienes permiso para modificar esta reserva.')
                    ->danger()
                    ->send();
                return;
            }

            ReservaIgnorada::create([
                'reserva_id' => $reservaId,
                'motivo' => $motivo,
                'notas' => $notas,
                'user_id' => digitra_user_id(),
            ]);

            // Eliminar corrección si existe
            ReservaCorregida::where('reserva_id', $reservaId)->delete();

            // Recargar la lista
            $data = $this->form->getState();
            $fechaInicio = Carbon::parse($data['fecha_inicio']);
            $fechaFin = Carbon::parse($data['fecha_fin']);
            $establecimientoId = $data['establecimiento_id'] ?? '0';
            $establecimientoId = ($establecimientoId === '0' || $establecimientoId === 0) ? null : (int)$establecimientoId;

            $this->reservasProblematicas = $this->obtenerReservasProblematicas($fechaInicio, $fechaFin, $establecimientoId);
            $this->totalProblemas = count($this->reservasProblematicas);

            Notification::make()
                ->title('✅ Reserva Ignorada')
                ->body("La reserva #{$reservaId} será excluida de los informes.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Restaurar una reserva
     */
    public function restaurarReserva($reservaId): void
    {
        try {
            // SEGURIDAD: Verificar que la reserva pertenece al tenant actual
            $reserva = Reserva::find($reservaId);
            if (!$reserva || !$this->reservaPerteneceAlTenant($reserva)) {
                Notification::make()
                    ->title('Error de Seguridad')
                    ->body('No tienes permiso para modificar esta reserva.')
                    ->danger()
                    ->send();
                return;
            }

            ReservaIgnorada::where('reserva_id', $reservaId)->delete();
            ReservaCorregida::where('reserva_id', $reservaId)->delete();

            // Recargar la lista
            $data = $this->form->getState();
            $fechaInicio = Carbon::parse($data['fecha_inicio']);
            $fechaFin = Carbon::parse($data['fecha_fin']);
            $establecimientoId = $data['establecimiento_id'] ?? '0';
            $establecimientoId = ($establecimientoId === '0' || $establecimientoId === 0) ? null : (int)$establecimientoId;

            $this->reservasProblematicas = $this->obtenerReservasProblematicas($fechaInicio, $fechaFin, $establecimientoId);
            $this->totalProblemas = count($this->reservasProblematicas);

            Notification::make()
                ->title('✅ Reserva Restaurada')
                ->body("La reserva #{$reservaId} ha sido restaurada.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Generar y descargar PDF
     */
    public function generarPDF(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        // Si ya hay vista previa, usar esos datos
        if ($this->mostrarVistaPrevia && $this->datosInforme && $this->insightsInforme) {
            $datosInforme = $this->datosInforme;
            $insights = $this->insightsInforme;
            $establecimientoId = $datosInforme['establecimiento']?->id ?? null;
            $fechaInicio = $datosInforme['periodo']['inicio'];
            $fechaFin = $datosInforme['periodo']['fin'];
        } else {
            // Generar datos desde formulario
            $data = $this->form->getState();

            $fechaInicio = Carbon::parse($data['fecha_inicio']);
            $fechaFin = Carbon::parse($data['fecha_fin']);

            // Convertir '0' (string) a null para informe general
            $establecimientoId = $data['establecimiento_id'] ?? '0';
            $establecimientoId = ($establecimientoId === '0' || $establecimientoId === 0) ? null : (int)$establecimientoId;

            // Validar que la fecha de inicio sea menor que la de fin
            if ($fechaInicio->gt($fechaFin)) {
                Notification::make()
                    ->title('Error en fechas')
                    ->body('La fecha de inicio debe ser anterior a la fecha de fin.')
                    ->danger()
                    ->send();

                return response()->streamDownload(function () {}, '');
            }

            // 🔒 VALIDACIÓN DE SEGURIDAD: Verificar que el establecimiento pertenece al usuario
            if ($establecimientoId) {
                $user = auth()->user();
                $digitraUserId = $user->tenant?->digitra_user_id;
                
                if (!$user->isSuperAdmin()) {
                    $establecimiento = Establecimiento::find($establecimientoId);
                    
                    if (!$establecimiento || $establecimiento->user_id !== $digitraUserId) {
                        Notification::make()
                            ->title('Error de Seguridad')
                            ->body('No tienes permiso para acceder a este establecimiento.')
                            ->danger()
                            ->send();
                        return response()->streamDownload(function () {}, '');
                    }
                }
            }
            
            // Generar datos del informe
            $informeService = new InformeService();
            $datosInforme = $informeService->generarDatosInforme($fechaInicio, $fechaFin, $establecimientoId);
            $insights = $informeService->generarInsights($datosInforme);
        }

        // Generar PDF
        $pdf = Pdf::loadView('pdf.informe', [
            'datos' => $datosInforme,
            'insights' => $insights,
        ]);

        // Configurar PDF
        $pdf->setPaper('letter', 'portrait');

        // Nombre del archivo según tipo de informe
        if ($establecimientoId && isset($datosInforme['establecimiento'])) {
            $nombreEstablecimiento = str_replace(' ', '_', $datosInforme['establecimiento']->nombre);
            $nombreArchivo = 'Informe_' . $nombreEstablecimiento . '_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd') . '.pdf';
        } else {
            $nombreArchivo = 'Informe_General_' . $fechaInicio->format('Ymd') . '_' . $fechaFin->format('Ymd') . '.pdf';
        }

        // 📊 TRACKING: Registrar descarga del informe
        try {
            $user = auth()->user();
            $tipoInforme = $establecimientoId ? 'especifico' : 'general';
            $establecimiento = $establecimientoId ? Establecimiento::find($establecimientoId) : null;

            InformeDescarga::create([
                'user_id' => $user?->id,
                'user_email' => $user?->email,
                'user_name' => $user?->name,
                'tipo_informe' => $tipoInforme,
                'establecimiento_id' => $establecimientoId,
                'establecimiento_nombre' => $establecimiento?->nombre,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'dias_periodo' => $fechaInicio->diffInDays($fechaFin),
                'nombre_archivo' => $nombreArchivo,
                'total_reservas' => $datosInforme['estadisticas_generales']['total_reservas'] ?? 0,
                'total_ingresos' => $datosInforme['estadisticas_generales']['total_ingresos'] ?? 0,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Silenciar errores de tracking para no afectar la descarga
            \Log::error('Error al registrar descarga de informe: ' . $e->getMessage());
        }

        Notification::make()
            ->title('Informe Generado')
            ->body('El informe PDF se ha generado correctamente.')
            ->success()
            ->send();

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $nombreArchivo);
    }

    /**
     * Acciones del header - Ahora vacío, los botones están en la vista
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * SEGURIDAD: Verificar que una reserva pertenece al tenant actual
     */
    private function reservaPerteneceAlTenant(Reserva $reserva): bool
    {
        $user = auth()->user();

        // Super admin puede modificar cualquier reserva
        if ($user->isSuperAdmin()) {
            return true;
        }

        $digitraUserId = $user->tenant?->digitra_user_id;
        if (!$digitraUserId) {
            return false;
        }

        // Verificar que el establecimiento de la reserva pertenece al usuario
        $establecimiento = $reserva->establecimiento;
        if (!$establecimiento) {
            return false;
        }

        return (int) $establecimiento->user_id === (int) $digitraUserId;
    }
}