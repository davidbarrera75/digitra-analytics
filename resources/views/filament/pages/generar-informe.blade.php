<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Información de la página --}}
        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-6 border border-blue-200 dark:border-blue-800">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        📊 Generador de Informes PDF
                    </h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-3">
                        Este generador crea informes profesionales en PDF con análisis completo de los datos de Digitra.
                    </p>
                    <div class="space-y-1 text-sm text-blue-600 dark:text-blue-400">
                        <p>✅ <strong>Estadísticas generales</strong> del período seleccionado</p>
                        <p>✅ <strong>Gráficas de tendencias</strong> de reservas y ocupación</p>
                        <p>✅ <strong>Top propiedades</strong> más rentables</p>
                        <p>✅ <strong>Insights inteligentes</strong> con recomendaciones</p>
                        <p>✅ <strong>Desglose detallado</strong> de ingresos por reserva</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Formulario de rango de fechas --}}
        <div>
            {{ $this->form }}
        </div>

        {{-- Botones de Acción --}}
        <div class="rounded-lg bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 border border-gray-200 dark:border-gray-700">
            {{-- Indicador de Carga --}}
            <div wire:loading wire:target="generarVistaPrevia,generarPDF" class="mb-4">
                <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-6 shadow-lg">
                    <div class="flex items-center justify-center gap-4">
                        <svg class="animate-spin h-8 w-8 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <div class="text-left">
                            <div class="text-lg font-bold text-blue-700 dark:text-blue-300">Generando Informe...</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span wire:loading wire:target="generarVistaPrevia">Analizando datos y validando calidad</span>
                                <span wire:loading wire:target="generarPDF">Creando documento PDF</span>
                            </div>
                        </div>
                    </div>

                    {{-- Barra de Progreso Animada --}}
                    <div class="mt-4 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 h-2.5 rounded-full animate-pulse" style="width: 100%;"></div>
                    </div>

                    <div class="mt-3 text-xs text-center text-gray-500 dark:text-gray-400">
                        ⏱️ Este proceso puede tardar unos segundos...
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 items-center justify-center">
                {{-- Botón Revisar Calidad (Paso 1) --}}
                <button
                    wire:click="revisarCalidad"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    type="button"
                    style="background-color: #d97706 !important; color: white !important;"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
                    onmouseover="this.style.backgroundColor='#b45309'"
                    onmouseout="this.style.backgroundColor='#d97706'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" wire:loading.remove wire:target="revisarCalidad">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="revisarCalidad">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="revisarCalidad">1️⃣ Revisar Calidad</span>
                    <span wire:loading wire:target="revisarCalidad">Revisando...</span>
                </button>

                {{-- Botón Vista Previa (Paso 2) --}}
                <button
                    wire:click="generarVistaPrevia"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    type="button"
                    style="background-color: #2563eb !important; color: white !important;"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    onmouseover="this.style.backgroundColor='#1d4ed8'"
                    onmouseout="this.style.backgroundColor='#2563eb'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" wire:loading.remove wire:target="generarVistaPrevia">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="generarVistaPrevia">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="generarVistaPrevia">2️⃣ Vista Previa</span>
                    <span wire:loading wire:target="generarVistaPrevia">Generando...</span>
                </button>

                {{-- Botón Descargar PDF (Paso 3) --}}
                <button
                    wire:click="generarPDF"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    type="button"
                    style="background-color: #16a34a !important; color: white !important;"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 font-semibold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    onmouseover="this.style.backgroundColor='#15803d'"
                    onmouseout="this.style.backgroundColor='#16a34a'"
                >
                    <svg class="w-5 h-5" fill="none" stroke="white" viewBox="0 0 24 24" wire:loading.remove wire:target="generarPDF">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" wire:loading wire:target="generarPDF">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="generarPDF">3️⃣ Descargar PDF</span>
                    <span wire:loading wire:target="generarPDF">Generando...</span>
                </button>
            </div>

            <p class="text-center text-sm text-gray-600 dark:text-gray-300 mt-4">
                💡 <strong class="text-gray-900 dark:text-white">Flujo Recomendado:</strong> 1) Revisar calidad → 2) Corregir problemas → 3) Ver preview → 4) Descargar PDF
            </p>
        </div>

        {{-- Revisión de Calidad de Datos --}}
        @if($mostrarRevisionCalidad)
            <div class="rounded-lg bg-white dark:bg-gray-900 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 border-2 border-orange-300 dark:border-orange-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-orange-900 dark:text-orange-100">
                        🔍 Revisión de Calidad de Datos
                    </h3>
                    <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $totalProblemas > 0 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' }}">
                        {{ $totalProblemas }} {{ $totalProblemas === 1 ? 'Problema' : 'Problemas' }}
                    </span>
                </div>

                @if($totalProblemas > 0)
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4 mb-4">
                        <p class="text-sm text-orange-700 dark:text-orange-300">
                            ⚠️ Se detectaron <strong>{{ $totalProblemas }} reservas con problemas</strong> en el período seleccionado. Revísalas y corrígelas antes de generar el informe para obtener datos precisos.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                                    <th class="px-4 py-3 text-left font-semibold">ID</th>
                                    <th class="px-4 py-3 text-left font-semibold">Propiedad</th>
                                    <th class="px-4 py-3 text-left font-semibold">Huésped</th>
                                    <th class="px-4 py-3 text-left font-semibold">Check-In</th>
                                    <th class="px-4 py-3 text-left font-semibold">Precio</th>
                                    <th class="px-4 py-3 text-left font-semibold">Problema</th>
                                    <th class="px-4 py-3 text-left font-semibold">Estado</th>
                                    <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reservasProblematicas as $reserva)
                                    <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800" wire:key="reserva-{{ $reserva['id'] }}">
                                        <td class="px-4 py-3">{{ $reserva['id'] }}</td>
                                        <td class="px-4 py-3">{{ Str::limit($reserva['establecimiento'], 20) }}</td>
                                        <td class="px-4 py-3">{{ Str::limit($reserva['huesped'], 20) }}</td>
                                        <td class="px-4 py-3">{{ $reserva['check_in'] }}</td>
                                        <td class="px-4 py-3">
                                            @if($reserva['estado'] === 'Corregida')
                                                <div>
                                                    <span class="line-through text-gray-400">${{ number_format($reserva['precio_original'], 0, ',', '.') }}</span>
                                                    <span class="text-green-600 dark:text-green-400 font-semibold">${{ number_format($reserva['precio_mostrar'], 0, ',', '.') }}</span>
                                                </div>
                                            @else
                                                <span class="{{ $reserva['precio_original'] < 100 || $reserva['precio_original'] > 10000000 ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">
                                                    ${{ number_format($reserva['precio_original'], 0, ',', '.') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200">
                                                {{ $reserva['problemas'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full {{ $reserva['estado'] === 'Ignorada' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' : ($reserva['estado'] === 'Corregida' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200') }}">
                                                {{ $reserva['estado'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex gap-1 justify-center" x-data="{ showModal: false, precioCorregido: {{ $reserva['precio_original'] }} }">
                                                @if($reserva['estado'] === 'Pendiente')
                                                    <button
                                                        @click="showModal = true"
                                                        class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700"
                                                        title="Corregir Precio"
                                                    >
                                                        ✏️
                                                    </button>
                                                    <button
                                                        wire:click="ignorarReserva({{ $reserva['id'] }})"
                                                        wire:confirm="¿Estás seguro de ignorar esta reserva? No se incluirá en los informes."
                                                        class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700"
                                                        title="Ignorar"
                                                    >
                                                        🚫
                                                    </button>
                                                @else
                                                    <button
                                                        wire:click="restaurarReserva({{ $reserva['id'] }})"
                                                        wire:confirm="¿Restaurar esta reserva a su estado original?"
                                                        class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700"
                                                        title="Restaurar"
                                                    >
                                                        ↩️
                                                    </button>
                                                @endif

                                                {{-- Modal para corregir precio --}}
                                                <div x-show="showModal" @click.away="showModal = false" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
                                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                                                        <h4 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Corregir Precio - Reserva #{{ $reserva['id'] }}</h4>
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Precio Original</label>
                                                                <input type="text" value="${{ number_format($reserva['precio_original'], 0, ',', '.') }}" disabled class="w-full px-3 py-2 border rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white">
                                                            </div>
                                                            <div>
                                                                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Precio Corregido *</label>
                                                                <input type="number" x-model="precioCorregido" class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Ingresa el precio correcto">
                                                            </div>
                                                            <div class="flex gap-2 justify-end">
                                                                <button @click="showModal = false" class="px-4 py-2 text-sm bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-300 dark:hover:bg-gray-600">
                                                                    Cancelar
                                                                </button>
                                                                <button
                                                                    @click="$wire.corregirPrecio({{ $reserva['id'] }}, precioCorregido); showModal = false"
                                                                    class="px-4 py-2 text-sm bg-green-600 text-white rounded hover:bg-green-700"
                                                                >
                                                                    Guardar
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 text-center">
                        <div class="text-4xl mb-2">✅</div>
                        <h4 class="text-lg font-bold text-green-900 dark:text-green-100 mb-2">
                            ¡Sin Problemas Detectados!
                        </h4>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            Todas las reservas del período seleccionado tienen datos válidos. Puedes generar el informe con confianza.
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- Vista Previa del Informe --}}
        @if($mostrarVistaPrevia && $datosInforme)
            <div class="mt-6">
                @include('informes.vista-previa', ['datos' => $datosInforme, 'insights' => $insightsInforme])
            </div>
        @endif

        {{-- Guía rápida --}}
        <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
            <h4 class="text-base font-semibold text-gray-900 dark:text-white mb-3">
                💡 Guía Rápida
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="space-y-2">
                    <p class="font-medium text-gray-700 dark:text-gray-300">Pasos para generar:</p>
                    <ol class="list-decimal list-inside space-y-1 text-gray-600 dark:text-gray-400">
                        <li>Selecciona el tipo de informe (general o específico)</li>
                        <li>Configura el rango de fechas</li>
                        <li>Haz clic en <strong>"Vista Previa"</strong> para ver el informe</li>
                        <li>Revisa los datos y el desglose de ingresos</li>
                        <li>Haz clic en <strong>"Descargar PDF"</strong> para obtener el archivo</li>
                    </ol>
                </div>
                <div class="space-y-2">
                    <p class="font-medium text-gray-700 dark:text-gray-300">Características:</p>
                    <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                        <li>Vista previa interactiva con desglose detallado</li>
                        <li>Ambos botones siempre disponibles</li>
                        <li>Períodos de 1-6 meses dan mejores insights</li>
                        <li>PDF incluye tabla completa de reservas</li>
                        <li>Los datos se cachean por 10 minutos</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
