<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.claude.api_key');
        $this->model = config('services.claude.model', 'claude-3-5-sonnet-20241022');
    }

    /**
     * Generar un resumen mensual usando Claude API
     *
     * @param array $datos Datos del usuario para el resumen
     * @return array ['contenido' => string, 'tokens' => int]
     */
    public function generarResumenMensual(array $datos): array
    {
        $prompt = $this->construirPromptResumenMensual($datos);

        return $this->enviarMensaje($prompt);
    }

    /**
     * Enviar un mensaje a Claude API
     *
     * @param string $mensaje
     * @param int $maxTokens
     * @return array ['contenido' => string, 'tokens' => int]
     */
    private function enviarMensaje(string $mensaje, int $maxTokens = 4096): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $mensaje,
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Claude API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception('Error al comunicarse con Claude API: ' . $response->body());
            }

            $data = $response->json();

            return [
                'contenido' => $data['content'][0]['text'] ?? '',
                'tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            ];

        } catch (\Exception $e) {
            Log::error('Claude Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Construir el prompt para el resumen mensual
     */
    private function construirPromptResumenMensual(array $datos): string
    {
        $mes = $datos['mes'];
        $año = $datos['año'];
        $nombreMes = $datos['nombre_mes'];
        $nombreUsuario = $datos['usuario']['name'] ?? 'Usuario';

        // Datos estadísticos
        $ingresos = $datos['estadisticas']['ingresos'] ?? 0;
        $gastosTotal = $datos['estadisticas']['gastos_total'] ?? 0;
        $utilidadNeta = $datos['estadisticas']['utilidad_neta'] ?? 0;
        $reservas = $datos['estadisticas']['reservas_count'] ?? 0;
        $noches = $datos['estadisticas']['noches_totales'] ?? 0;
        $ocupacion = $datos['estadisticas']['porcentaje_ocupacion'] ?? 0;
        $precioPromedio = $datos['estadisticas']['precio_promedio_noche'] ?? 0;

        // Datos del mes anterior para comparación
        $ingresosAnterior = $datos['mes_anterior']['ingresos'] ?? 0;
        $reservasAnterior = $datos['mes_anterior']['reservas_count'] ?? 0;
        $ocupacionAnterior = $datos['mes_anterior']['porcentaje_ocupacion'] ?? 0;

        // Propiedades
        $propiedades = $datos['propiedades'] ?? [];
        $totalPropiedades = count($propiedades);

        $prompt = <<<EOT
Eres un asistente financiero experto en análisis de propiedades de alquiler vacacional. Genera un resumen ejecutivo mensual en español para el propietario.

**Datos del Usuario:**
- Nombre: {$nombreUsuario}
- Período: {$nombreMes} {$año}
- Propiedades activas: {$totalPropiedades}

**Métricas del Mes:**
- Ingresos Totales: $" . number_format($ingresos, 0, ',', '.') . " COP
- Gastos Operativos: $" . number_format($gastosTotal, 0, ',', '.') . " COP
- Utilidad Neta: $" . number_format($utilidadNeta, 0, ',', '.') . " COP
- Reservas: {$reservas} reservas
- Noches Reservadas: {$noches} noches
- Ocupación: " . round($ocupacion, 1) . "%
- Precio Promedio por Noche: $" . number_format($precioPromedio, 0, ',', '.') . " COP

**Comparación con Mes Anterior:**
- Ingresos Anteriores: $" . number_format($ingresosAnterior, 0, ',', '.') . " COP
- Reservas Anteriores: {$reservasAnterior}
- Ocupación Anterior: " . round($ocupacionAnterior, 1) . "%

**Instrucciones:**

Genera un resumen ejecutivo en formato Markdown siguiendo EXACTAMENTE esta estructura:

1. **Saludo personalizado** usando el nombre del usuario
2. **Resumen Ejecutivo** (2-3 oraciones destacando los puntos más importantes)
3. **💡 Insights Clave** (3-5 bullets con hallazgos importantes y análisis)
4. **🎯 Recomendaciones Accionables** (3-4 sugerencias concretas para mejorar)
5. **📊 Comparación Mensual** (tabla comparativa con el mes anterior mostrando cambios porcentuales)
6. **🔮 Perspectiva para el Próximo Mes** (1-2 oraciones sobre qué esperar)

**Requisitos:**
- Usa un tono profesional pero cercano
- Incluye emojis relevantes para cada sección
- Muestra porcentajes de cambio (ej: +12% 🟢, -5% 🔴)
- Da contexto a los números (si ocupación es 74%, menciona si está por encima/debajo del promedio del mercado ~65%)
- Las recomendaciones deben ser específicas y accionables
- Usa formato Markdown para tablas y listas
- NO uses asteriscos para negrita, usa formato Markdown estándar (** para bold)
- Mantén el tono positivo pero honesto

Genera el resumen ahora:
EOT;

        return $prompt;
    }
}
