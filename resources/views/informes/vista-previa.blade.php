<div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-8 max-w-6xl mx-auto border border-gray-200 dark:border-gray-700">
    {{-- Header --}}
    <div style="background-color: #7c3aed !important; color: white !important;" class="p-8 rounded-lg mb-6">
        <h1 class="text-3xl font-bold mb-2 text-white">📊 Informe Digitra Analytics</h1>
        @if(isset($datos['establecimiento']) && $datos['establecimiento'])
            <div class="text-lg opacity-90 text-white">Informe Individual - {{ $datos['establecimiento']->nombre }}</div>
            @if($datos['establecimiento']->rnt)
                <div class="text-sm mt-1 text-white">RNT: {{ $datos['establecimiento']->rnt }}</div>
            @endif
        @else
            <div class="text-lg opacity-90 text-white">Informe General - Todos los Establecimientos</div>
        @endif
    </div>

    {{-- Período --}}
    <div class="bg-gray-50 dark:bg-gray-800 border-l-4 border-purple-600 p-4 mb-6 rounded">
        <div class="font-semibold text-purple-600 dark:text-purple-400 mb-2">Período Analizado:</div>
        <div class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
            <div><strong>Desde:</strong> {{ $datos['periodo']['inicio']->format('d/m/Y') }}</div>
            <div><strong>Hasta:</strong> {{ $datos['periodo']['fin']->format('d/m/Y') }}</div>
            <div><strong>Total:</strong> {{ $datos['periodo']['dias'] }} días ({{ $datos['periodo']['meses'] }} {{ $datos['periodo']['meses'] > 1 ? 'meses' : 'mes' }})</div>
            <div class="text-gray-500 dark:text-gray-400"><small>Generado el: {{ now()->format('d/m/Y H:i') }}</small></div>
        </div>
    </div>

    {{-- Alertas de Calidad de Datos --}}
    @if($datos['alertas']['tiene_problemas'])
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-red-600 pb-2">
            🚨 Alertas de Calidad de Datos
        </h2>

        {{-- Resumen de Alertas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            @if($datos['alertas']['total_alertas'] > 0)
            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-lg shadow border-2 border-red-300 dark:border-red-700">
                <div class="text-4xl font-bold text-red-600 dark:text-red-400 text-center">
                    {{ $datos['alertas']['total_alertas'] }}
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300 mt-2 text-center font-semibold">
                    Error{{ $datos['alertas']['total_alertas'] > 1 ? 'es' : '' }} Crítico{{ $datos['alertas']['total_alertas'] > 1 ? 's' : '' }}
                </div>
            </div>
            @endif

            @if($datos['alertas']['total_advertencias'] > 0)
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-6 rounded-lg shadow border-2 border-yellow-300 dark:border-yellow-700">
                <div class="text-4xl font-bold text-yellow-600 dark:text-yellow-400 text-center">
                    {{ $datos['alertas']['total_advertencias'] }}
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300 mt-2 text-center font-semibold">
                    Advertencia{{ $datos['alertas']['total_advertencias'] > 1 ? 's' : '' }}
                </div>
            </div>
            @endif
        </div>

        {{-- Lista de Errores Críticos --}}
        @if(count($datos['alertas']['alertas']) > 0)
        <div class="mb-6">
            <h3 class="text-lg font-bold text-red-700 dark:text-red-400 mb-3">🔴 Errores Críticos</h3>
            <div class="space-y-3">
                @foreach($datos['alertas']['alertas'] as $alerta)
                <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-600 p-4 rounded shadow">
                    <div class="flex items-start">
                        <div class="text-3xl mr-3">{{ $alerta['icono'] }}</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-red-800 dark:text-red-300 mb-1">{{ $alerta['titulo'] }}</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{{ $alerta['descripcion'] }}</p>
                            <div class="bg-white dark:bg-gray-800 p-2 rounded border border-red-200 dark:border-red-800">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <strong class="text-red-700 dark:text-red-400">💡 Recomendación:</strong> {{ $alerta['recomendacion'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Lista de Advertencias --}}
        @if(count($datos['alertas']['advertencias']) > 0)
        <div>
            <h3 class="text-lg font-bold text-yellow-700 dark:text-yellow-400 mb-3">⚠️ Advertencias</h3>
            <div class="space-y-3">
                @foreach($datos['alertas']['advertencias'] as $advertencia)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded shadow">
                    <div class="flex items-start">
                        <div class="text-3xl mr-3">{{ $advertencia['icono'] }}</div>
                        <div class="flex-1">
                            <h4 class="font-bold text-yellow-800 dark:text-yellow-300 mb-1">{{ $advertencia['titulo'] }}</h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{{ $advertencia['descripcion'] }}</p>
                            <div class="bg-white dark:bg-gray-800 p-2 rounded border border-yellow-200 dark:border-yellow-800">
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    <strong class="text-yellow-700 dark:text-yellow-400">💡 Recomendación:</strong> {{ $advertencia['recomendacion'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Estadísticas Generales --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-purple-600 pb-2">📈 Estadísticas Generales</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-blue-600">{{ number_format($datos['estadisticas_generales']['total_reservas']) }}</div>
                <div class="text-sm text-gray-600 mt-1">Reservas</div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-green-600">${{ number_format($datos['estadisticas_generales']['total_ingresos'], 0, ',', '.') }}</div>
                <div class="text-sm text-gray-600 mt-1">Ingresos Totales</div>
            </div>
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($datos['estadisticas_generales']['total_huespedes']) }}</div>
                <div class="text-sm text-gray-600 mt-1">Huéspedes Únicos</div>
            </div>
        </div>

        {{-- Detalle de Reservas (Desplegable) --}}
        @if(isset($datos['reservas_detalle']) && count($datos['reservas_detalle']) > 0)
            <div class="mb-4">
                <details class="bg-green-50 border border-green-200 rounded-lg overflow-hidden">
                    <summary class="cursor-pointer px-6 py-4 bg-gradient-to-r from-green-100 to-green-50 hover:from-green-200 hover:to-green-100 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">📋</span>
                                <div>
                                    <h3 class="text-lg font-semibold text-green-800">Detalle de Ingresos</h3>
                                    <p class="text-sm text-green-600">Click para ver el desglose de las {{ number_format(count($datos['reservas_detalle'])) }} reservas</p>
                                </div>
                            </div>
                            <svg class="w-6 h-6 text-green-600 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </summary>
                    <div class="p-6 bg-white">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border border-gray-200">
                                <thead class="bg-green-600 text-white">
                                    <tr>
                                        <th class="py-3 px-4 text-left text-sm font-semibold">#</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold">Check-In</th>
                                        <th class="py-3 px-4 text-left text-sm font-semibold">Check-Out</th>
                                        <th class="py-3 px-4 text-center text-sm font-semibold">Noches</th>
                                        @if(!isset($datos['establecimiento']))
                                            <th class="py-3 px-4 text-left text-sm font-semibold">Propiedad</th>
                                        @endif
                                        <th class="py-3 px-4 text-left text-sm font-semibold">Huésped</th>
                                        <th class="py-3 px-4 text-right text-sm font-semibold">Precio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $subtotal = 0; @endphp
                                    @foreach($datos['reservas_detalle'] as $index => $reserva)
                                    @php $subtotal += $reserva['precio']; @endphp
                                    <tr class="{{ $index % 2 == 0 ? 'bg-gray-50' : 'bg-white' }} hover:bg-green-50 transition-colors">
                                        <td class="py-2 px-4 border-b text-sm">{{ $index + 1 }}</td>
                                        <td class="py-2 px-4 border-b text-sm">
                                            {{ \Carbon\Carbon::parse($reserva['check_in'])->format('d/m/Y') }}
                                        </td>
                                        <td class="py-2 px-4 border-b text-sm">
                                            {{ $reserva['check_out'] ? \Carbon\Carbon::parse($reserva['check_out'])->format('d/m/Y') : 'N/A' }}
                                        </td>
                                        <td class="py-2 px-4 border-b text-sm text-center">
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs font-semibold">
                                                {{ $reserva['noches'] }}
                                            </span>
                                        </td>
                                        @if(!isset($datos['establecimiento']))
                                            <td class="py-2 px-4 border-b text-sm">{{ $reserva['establecimiento'] }}</td>
                                        @endif
                                        <td class="py-2 px-4 border-b text-sm">{{ $reserva['huesped'] }}</td>
                                        <td class="py-2 px-4 border-b text-sm text-right font-semibold text-green-700">
                                            ${{ number_format($reserva['precio'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                    {{-- Fila de Total --}}
                                    <tr class="bg-green-100 font-bold">
                                        <td colspan="{{ isset($datos['establecimiento']) ? '5' : '6' }}" class="py-3 px-4 text-right text-sm uppercase text-green-800">
                                            Total Ingresos:
                                        </td>
                                        <td class="py-3 px-4 text-right text-lg text-green-700">
                                            ${{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 bg-blue-50 p-3 rounded border border-blue-200">
                            <strong>💡 Nota:</strong> Este detalle muestra todas las reservas del período seleccionado ordenadas por fecha de check-in más reciente.
                        </div>
                    </div>
                </details>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-orange-600">{{ number_format($datos['estadisticas_generales']['total_establecimientos']) }}</div>
                <div class="text-sm text-gray-600 mt-1">Propiedades Activas</div>
            </div>
            <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-teal-600">{{ number_format($datos['estadisticas_generales']['promedio_reservas_por_dia'], 1) }}</div>
                <div class="text-sm text-gray-600 mt-1">Reservas/Día</div>
            </div>
            <div class="bg-gradient-to-br from-pink-50 to-pink-100 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-pink-600">${{ number_format($datos['estadisticas_generales']['promedio_ingresos_por_reserva'], 0, ',', '.') }}</div>
                <div class="text-sm text-gray-600 mt-1">Promedio/Reserva</div>
            </div>
        </div>
    </div>

    {{-- Noches Reservadas por Mes --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-purple-600 pb-2">🌙 Noches Reservadas por Mes</h2>

        {{-- Total de Noches --}}
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 p-6 rounded-lg shadow mb-6">
            <div class="text-4xl font-bold text-indigo-600 dark:text-indigo-400 text-center">
                {{ number_format($datos['noches_por_mes']['total_noches']) }}
            </div>
            <div class="text-sm text-gray-700 dark:text-gray-300 mt-2 text-center font-semibold">Total de Noches en el Período</div>
        </div>

        {{-- Gráfica de Barras --}}
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow border border-gray-200 dark:border-gray-700">
            <div class="space-y-4">
                @php
                    $maxNoches = max($datos['noches_por_mes']['valores']);
                @endphp
                @foreach($datos['noches_por_mes']['labels'] as $index => $mes)
                    @php
                        $noches = $datos['noches_por_mes']['valores'][$index];
                        $porcentaje = $maxNoches > 0 ? ($noches / $maxNoches) * 100 : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 w-24">{{ $mes }}</span>
                            <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($noches) }} noches</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-8 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-8 rounded-full flex items-center justify-end pr-3 transition-all duration-500"
                                 style="width: {{ $porcentaje }}%">
                                @if($porcentaje > 15)
                                    <span class="text-white text-xs font-bold">{{ number_format($noches) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Promedio de Noches por Mes --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($datos['noches_por_mes']['total_noches'] / max(count($datos['noches_por_mes']['labels']), 1), 1) }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Promedio de Noches/Mes</div>
            </div>
            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    {{ $datos['estadisticas_generales']['total_reservas'] > 0 ? number_format($datos['noches_por_mes']['total_noches'] / $datos['estadisticas_generales']['total_reservas'], 1) : 0 }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Promedio de Noches/Reserva</div>
            </div>
        </div>
    </div>

    {{-- Insights --}}
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-purple-600 pb-2">💡 Insights y Análisis</h2>

        <div class="space-y-4">
            @foreach($insights as $insight)
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                <div class="flex items-start">
                    <div class="text-4xl mr-4">{{ $insight['icono'] }}</div>
                    <div class="flex-1">
                        <h3 class="text-blue-700 font-semibold text-lg">{{ $insight['titulo'] }}</h3>
                        <div class="text-2xl font-bold text-gray-800 my-1">{{ $insight['valor'] }}</div>
                        <div class="text-gray-600 text-sm">{{ $insight['descripcion'] }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top Propiedades --}}
    <div class="mb-8">
        @if(isset($datos['establecimiento']) && $datos['establecimiento'])
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-purple-600 pb-2">🏢 Información de la Propiedad</h2>
        @else
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-purple-600 pb-2">🏆 Top 10 Propiedades por Reservas</h2>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700">
                <thead class="bg-purple-600 text-white">
                    <tr>
                        <th class="py-3 px-4 text-left">#</th>
                        <th class="py-3 px-4 text-left">Propiedad</th>
                        <th class="py-3 px-4 text-left">Propietario</th>
                        <th class="py-3 px-4 text-right">Reservas</th>
                        <th class="py-3 px-4 text-left">RNT</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 dark:text-gray-300">
                    @foreach($datos['top_propiedades'] as $index => $propiedad)
                    <tr class="{{ $index % 2 == 0 ? 'bg-gray-50 dark:bg-gray-900' : 'bg-white dark:bg-gray-800' }}">
                        <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700"><strong>{{ $index + 1 }}</strong></td>
                        <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700">{{ $propiedad['nombre'] }}</td>
                        <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700">{{ $propiedad['propietario'] }}</td>
                        <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-right">
                            <span class="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded text-sm font-semibold">
                                {{ number_format($propiedad['reservas']) }}
                            </span>
                        </td>
                        <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700">{{ $propiedad['rnt'] ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gastos Operacionales --}}
    @if($datos['gastos']['tiene_gastos'])
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4 border-b-2 border-green-600 pb-2">💰 Gastos Operacionales</h2>

        {{-- Resumen de Totales --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-red-600 dark:text-red-400">${{ number_format($datos['gastos']['total_aseo'], 0, ',', '.') }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Aseo</div>
            </div>
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">${{ number_format($datos['gastos']['total_administracion'], 0, ',', '.') }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Administración</div>
            </div>
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 p-6 rounded-lg shadow">
                <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">${{ number_format($datos['gastos']['total_otros'], 0, ',', '.') }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Otros Gastos</div>
            </div>
            <div class="bg-gradient-to-br from-red-100 to-red-200 dark:from-red-800/30 dark:to-red-700/30 p-6 rounded-lg shadow border-2 border-red-400">
                <div class="text-3xl font-bold text-red-700 dark:text-red-300">${{ number_format($datos['gastos']['total_gastos'], 0, ',', '.') }}</div>
                <div class="text-sm text-gray-700 dark:text-gray-300 mt-1 font-semibold">TOTAL GASTOS</div>
            </div>
        </div>

        {{-- Detalle por Mes --}}
        @if(count($datos['gastos']['gastos_por_mes']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200 dark:border-gray-700">
                    <thead class="bg-green-600 text-white">
                        <tr>
                            <th class="py-3 px-4 text-left text-sm font-semibold">Período</th>
                            <th class="py-3 px-4 text-right text-sm font-semibold">Aseo</th>
                            <th class="py-3 px-4 text-right text-sm font-semibold">Administración</th>
                            <th class="py-3 px-4 text-right text-sm font-semibold">Otros</th>
                            <th class="py-3 px-4 text-right text-sm font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos['gastos']['gastos_por_mes'] as $index => $gasto)
                        <tr class="{{ $index % 2 == 0 ? 'bg-gray-50 dark:bg-gray-900' : 'bg-white dark:bg-gray-800' }}">
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 font-semibold">{{ $gasto['periodo'] }}</td>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-right">${{ number_format($gasto['aseo'], 0, ',', '.') }}</td>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-right">${{ number_format($gasto['administracion'], 0, ',', '.') }}</td>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-right">${{ number_format($gasto['otros_gastos'], 0, ',', '.') }}</td>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-right font-bold text-green-700 dark:text-green-400">${{ number_format($gasto['total'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Balance Financiero --}}
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-6 rounded-lg shadow-lg border-2 border-blue-300 dark:border-blue-700">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">📊 Balance Financiero del Período</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Ingresos Totales</div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">${{ number_format($datos['estadisticas_generales']['total_ingresos'], 0, ',', '.') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Gastos Totales</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">-${{ number_format($datos['gastos']['total_gastos'], 0, ',', '.') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Balance Neto</div>
                    <div class="text-3xl font-bold text-blue-700 dark:text-blue-300">${{ number_format($datos['estadisticas_generales']['total_ingresos'] - $datos['gastos']['total_gastos'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Notas --}}
        @if(count($datos['gastos']['notas']) > 0)
        <div class="mt-6 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-500 p-4 rounded">
            <h4 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2">📝 Notas de Gastos:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-gray-700 dark:text-gray-300">
                @foreach($datos['gastos']['notas'] as $nota)
                    <li>{{ $nota }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    {{-- Footer --}}
    <div class="mt-8 pt-6 border-t-2 border-purple-600 text-center text-gray-600 dark:text-gray-400">
        <p class="font-semibold">Digitra Analytics - Sistema de Análisis de Datos</p>
        <p class="text-sm">Informe generado automáticamente el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
        <p class="text-xs mt-2 text-gray-500 dark:text-gray-500">Este informe contiene datos confidenciales. Uso exclusivo para análisis interno.</p>
    </div>
</div>
