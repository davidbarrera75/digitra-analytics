<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe Digitra Analytics - {{ isset($datos['establecimiento']) ? $datos['establecimiento']->nombre : 'General' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #2d3748;
            line-height: 1.5;
            background-color: #ffffff;
        }

        /* Header Mejorado */
        .header {
            background-color: #7c3aed;
            color: #ffffff;
            padding: 40px 30px;
            margin-bottom: 30px;
            border-radius: 0 0 15px 15px;
        }

        .header h1 {
            font-size: 32pt;
            margin-bottom: 8px;
            font-weight: bold;
            letter-spacing: -0.5px;
            color: #ffffff;
        }

        .header .property-name {
            font-size: 20pt;
            margin-bottom: 10px;
            font-weight: 600;
            opacity: 0.95;
            color: #ffffff;
        }

        .header .subtitle {
            font-size: 12pt;
            opacity: 0.85;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .header .rnt {
            font-size: 10pt;
            opacity: 0.75;
            font-style: italic;
            color: #ffffff;
        }

        /* Período Mejorado */
        .periodo {
            background-color: #f7fafc;
            border-left: 5px solid #7c3aed;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .periodo-title {
            color: #7c3aed;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .periodo-info {
            font-size: 10pt;
            line-height: 1.8;
        }

        .periodo-info strong {
            color: #4a5568;
        }

        /* Secciones */
        .section {
            margin-bottom: 35px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #7c3aed;
            color: #ffffff;
            padding: 12px 20px;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.3);
        }

        /* Stats Grid Mejorado */
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-spacing: 10px;
        }

        .stats-row {
            display: table-row;
        }

        .stat-card {
            display: table-cell;
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            padding: 20px;
            text-align: center;
            width: 33.33%;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .stat-value {
            font-size: 26pt;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-value.success {
            color: #48bb78;
        }

        .stat-value.info {
            color: #4299e1;
        }

        .stat-value.warning {
            color: #ed8936;
        }

        .stat-label {
            font-size: 9pt;
            color: #718096;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Insights Mejorados */
        .insight-box {
            background-color: #ebf8ff;
            border-left: 5px solid #4299e1;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .insight-icon {
            font-size: 28pt;
            float: left;
            margin-right: 18px;
            line-height: 1;
        }

        .insight-content h4 {
            color: #2b6cb0;
            font-size: 11pt;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .insight-value {
            font-size: 20pt;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 6px;
            line-height: 1;
        }

        .insight-desc {
            font-size: 9pt;
            color: #4a5568;
            line-height: 1.5;
        }

        /* Tablas Mejoradas */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background-color: #7c3aed;
            color: #ffffff;
            padding: 12px 10px;
            text-align: left;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            border: 1px solid #e2e8f0;
            padding: 10px;
            font-size: 9pt;
        }

        .table tr:nth-child(even) {
            background-color: #f7fafc;
        }

        .table tr:hover {
            background-color: #edf2f7;
        }

        .table .total-row {
            background-color: #16a34a;
            color: #ffffff;
            font-weight: bold;
            font-size: 10pt;
        }

        .table .total-row td {
            border-color: #48bb78;
            padding: 14px 10px;
        }

        /* Detalle de Reservas */
        .detalle-header {
            background-color: #16a34a;
            color: #ffffff;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
        }

        .detalle-header h3 {
            font-size: 13pt;
            margin-bottom: 5px;
            color: #ffffff;
        }

        .detalle-header p {
            font-size: 9pt;
            opacity: 0.9;
            color: #ffffff;
        }

        .detalle-note {
            background-color: #ebf8ff;
            border: 1px solid #bee3f8;
            padding: 12px;
            margin-top: 15px;
            border-radius: 6px;
            font-size: 8pt;
            color: #2c5282;
            line-height: 1.6;
        }

        /* Gráfica Mejorada */
        .chart-container {
            margin: 25px 0;
            padding: 25px;
            background-color: #f7fafc;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .chart-title {
            text-align: center;
            color: #4a5568;
            font-size: 10pt;
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Badges Mejorados */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.3px;
        }

        .badge-success {
            background-color: #c6f6d5;
            color: #22543d;
        }

        .badge-info {
            background-color: #bee3f8;
            color: #2c5282;
        }

        .badge-warning {
            background-color: #feebc8;
            color: #7c2d12;
        }

        /* Footer Mejorado */
        .footer {
            margin-top: 50px;
            padding-top: 25px;
            border-top: 3px solid #667eea;
            text-align: center;
            font-size: 8pt;
            color: #718096;
            line-height: 1.8;
        }

        .footer strong {
            color: #667eea;
            font-size: 10pt;
        }

        .page-break {
            page-break-after: always;
        }

        /* Números destacados */
        .highlight-number {
            color: #48bb78;
            font-weight: bold;
        }

        /* Alineación */
        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    {{-- Header con nombre de propiedad destacado --}}
    <div class="header">
        <h1>📊 Informe Digitra Analytics</h1>
        @if(isset($datos['establecimiento']) && $datos['establecimiento'])
            <div class="property-name">{{ $datos['establecimiento']->nombre }}</div>
            <div class="subtitle">Informe Individual de Propiedad</div>
            @if($datos['establecimiento']->rnt)
                <div class="rnt">RNT: {{ $datos['establecimiento']->rnt }}</div>
            @endif
        @else
            <div class="property-name">Informe General</div>
            <div class="subtitle">Todas las Propiedades</div>
        @endif
    </div>

    {{-- Período --}}
    <div class="periodo">
        <div class="periodo-title">📅 Período Analizado</div>
        <div class="periodo-info">
            <strong>Desde:</strong> {{ $datos['periodo']['inicio']->format('d/m/Y') }}<br>
            <strong>Hasta:</strong> {{ $datos['periodo']['fin']->format('d/m/Y') }}<br>
            <strong>Duración:</strong> {{ $datos['periodo']['dias'] }} días ({{ $datos['periodo']['meses'] }} {{ $datos['periodo']['meses'] > 1 ? 'meses' : 'mes' }})<br>
            <small style="color: #718096;">Generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</small>
        </div>
    </div>

    {{-- Alertas de Calidad de Datos --}}
    @if($datos['alertas']['tiene_problemas'])
    <div class="section">
        <div class="section-title" style="background-color: #dc2626; color: #ffffff;">
            🚨 Alertas de Calidad de Datos
        </div>

        {{-- Resumen de Alertas --}}
        <div class="stats-grid" style="margin-bottom: 25px;">
            <div class="stats-row">
                @if($datos['alertas']['total_alertas'] > 0)
                <div class="stat-card" style="border: 3px solid #dc2626; background-color: #fee2e2;">
                    <div class="stat-value" style="color: #dc2626; font-size: 32pt;">
                        {{ $datos['alertas']['total_alertas'] }}
                    </div>
                    <div class="stat-label" style="color: #991b1b;">
                        Error{{ $datos['alertas']['total_alertas'] > 1 ? 'es' : '' }} Crítico{{ $datos['alertas']['total_alertas'] > 1 ? 's' : '' }}
                    </div>
                </div>
                @endif

                @if($datos['alertas']['total_advertencias'] > 0)
                <div class="stat-card" style="border: 3px solid #d97706; background-color: #fef3c7;">
                    <div class="stat-value" style="color: #d97706; font-size: 32pt;">
                        {{ $datos['alertas']['total_advertencias'] }}
                    </div>
                    <div class="stat-label" style="color: #92400e;">
                        Advertencia{{ $datos['alertas']['total_advertencias'] > 1 ? 's' : '' }}
                    </div>
                </div>
                @endif

                <div class="stat-card" style="border: 3px solid #4b5563; background-color: #f3f4f6;">
                    <div class="stat-value" style="color: #4b5563; font-size: 18pt; margin-top: 10px;">
                        Calidad de Datos
                    </div>
                    <div style="font-size: 8pt; color: #6b7280; margin-top: 5px; line-height: 1.4;">
                        Se detectaron {{ $datos['alertas']['total_alertas'] + $datos['alertas']['total_advertencias'] }} problema(s) que requieren atención
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Errores Críticos --}}
        @if(count($datos['alertas']['alertas']) > 0)
        <div style="margin-bottom: 25px;">
            <h3 style="color: #dc2626; font-size: 11pt; font-weight: bold; margin-bottom: 12px; padding-bottom: 5px; border-bottom: 2px solid #dc2626;">
                🔴 Errores Críticos
            </h3>
            @foreach($datos['alertas']['alertas'] as $alerta)
            <div style="background-color: #fee2e2; border-left: 4px solid #dc2626; padding: 12px; margin-bottom: 10px; border-radius: 4px;">
                <div style="font-weight: bold; color: #991b1b; font-size: 10pt; margin-bottom: 5px;">
                    {{ $alerta['icono'] }} {{ $alerta['titulo'] }}
                </div>
                <div style="font-size: 9pt; color: #4a5568; margin-bottom: 8px; line-height: 1.4;">
                    {{ $alerta['descripcion'] }}
                </div>
                <div style="background-color: #ffffff; padding: 8px; border: 1px solid #fecaca; border-radius: 3px;">
                    <div style="font-size: 8pt; color: #6b7280;">
                        <strong style="color: #dc2626;">💡 Recomendación:</strong> {{ $alerta['recomendacion'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Lista de Advertencias --}}
        @if(count($datos['alertas']['advertencias']) > 0)
        <div>
            <h3 style="color: #d97706; font-size: 11pt; font-weight: bold; margin-bottom: 12px; padding-bottom: 5px; border-bottom: 2px solid #d97706;">
                ⚠️ Advertencias
            </h3>
            @foreach($datos['alertas']['advertencias'] as $advertencia)
            <div style="background-color: #fef3c7; border-left: 4px solid #d97706; padding: 12px; margin-bottom: 10px; border-radius: 4px;">
                <div style="font-weight: bold; color: #92400e; font-size: 10pt; margin-bottom: 5px;">
                    {{ $advertencia['icono'] }} {{ $advertencia['titulo'] }}
                </div>
                <div style="font-size: 9pt; color: #4a5568; margin-bottom: 8px; line-height: 1.4;">
                    {{ $advertencia['descripcion'] }}
                </div>
                <div style="background-color: #ffffff; padding: 8px; border: 1px solid #fde68a; border-radius: 3px;">
                    <div style="font-size: 8pt; color: #6b7280;">
                        <strong style="color: #d97706;">💡 Recomendación:</strong> {{ $advertencia['recomendacion'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Salto de página si hay alertas --}}
    <div class="page-break"></div>
    @endif

    {{-- Estadísticas Generales --}}
    <div class="section">
        <div class="section-title">📈 Resumen Ejecutivo</div>

        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value info">{{ number_format($datos['estadisticas_generales']['total_reservas']) }}</div>
                    <div class="stat-label">Total Reservas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value success">${{ number_format($datos['estadisticas_generales']['total_ingresos'], 0, ',', '.') }}</div>
                    <div class="stat-label">Ingresos Totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${{ number_format($datos['estadisticas_generales']['promedio_ingresos_por_reserva'], 0, ',', '.') }}</div>
                    <div class="stat-label">Promedio/Reserva</div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value warning">{{ number_format($datos['estadisticas_generales']['total_huespedes']) }}</div>
                    <div class="stat-label">Huéspedes Únicos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value info">{{ number_format($datos['estadisticas_generales']['total_establecimientos']) }}</div>
                    <div class="stat-label">Propiedades</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ number_format($datos['estadisticas_generales']['promedio_reservas_por_dia'], 1) }}</div>
                    <div class="stat-label">Reservas/Día</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Noches Reservadas por Mes --}}
    <div class="section">
        <div class="section-title">🌙 Noches Reservadas por Mes</div>

        {{-- Total de Noches --}}
        <div style="background-color: #eef2ff; border: 2px solid #c7d2fe; padding: 20px; margin-bottom: 20px; border-radius: 10px; text-align: center;">
            <div style="font-size: 36pt; font-weight: bold; color: #6366f1; margin-bottom: 8px;">
                {{ number_format($datos['noches_por_mes']['total_noches']) }}
            </div>
            <div style="font-size: 10pt; color: #4b5563; font-weight: 600;">Total de Noches en el Período</div>
        </div>

        {{-- Tabla de Noches por Mes --}}
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50%;">Mes</th>
                    <th style="width: 25%; text-align: center;">Noches</th>
                    <th style="width: 25%; text-align: center;">% del Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalNoches = $datos['noches_por_mes']['total_noches'];
                @endphp
                @foreach($datos['noches_por_mes']['labels'] as $index => $mes)
                    @php
                        $noches = $datos['noches_por_mes']['valores'][$index];
                        $porcentaje = $totalNoches > 0 ? ($noches / $totalNoches) * 100 : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $mes }}</strong></td>
                        <td style="text-align: center;">
                            <span class="badge badge-info">{{ number_format($noches) }}</span>
                        </td>
                        <td style="text-align: center;">{{ number_format($porcentaje, 1) }}%</td>
                    </tr>
                @endforeach
                {{-- Fila de Total --}}
                <tr class="total-row">
                    <td style="text-align: right; text-transform: uppercase;">
                        <strong>TOTAL</strong>
                    </td>
                    <td style="text-align: center; font-size: 11pt;">
                        {{ number_format($totalNoches) }}
                    </td>
                    <td style="text-align: center; font-size: 11pt;">
                        100%
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Métricas Promedio --}}
        <div class="stats-grid" style="margin-top: 20px;">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value info">
                        {{ number_format($datos['noches_por_mes']['total_noches'] / max(count($datos['noches_por_mes']['labels']), 1), 1) }}
                    </div>
                    <div class="stat-label">Promedio Noches/Mes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        {{ $datos['estadisticas_generales']['total_reservas'] > 0 ? number_format($datos['noches_por_mes']['total_noches'] / $datos['estadisticas_generales']['total_reservas'], 1) : 0 }}
                    </div>
                    <div class="stat-label">Promedio Noches/Reserva</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value success">
                        ${{ $datos['noches_por_mes']['total_noches'] > 0 ? number_format($datos['estadisticas_generales']['total_ingresos'] / $datos['noches_por_mes']['total_noches'], 0, ',', '.') : 0 }}
                    </div>
                    <div class="stat-label">Ingreso por Noche</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle de Ingresos (Nuevo) --}}
    @if(isset($datos['reservas_detalle']) && count($datos['reservas_detalle']) > 0)
    <div class="section">
        <div class="detalle-header">
            <h3>💰 Detalle de Ingresos por Reserva</h3>
            <p>Desglose completo de las {{ number_format(count($datos['reservas_detalle'])) }} reservas del período</p>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 12%;">Check-In</th>
                    <th style="width: 12%;">Check-Out</th>
                    <th style="width: 8%; text-align: center;">Noches</th>
                    @if(!isset($datos['establecimiento']))
                        <th style="width: 25%;">Propiedad</th>
                    @endif
                    <th style="width: {{ isset($datos['establecimiento']) ? '38%' : '23%' }};">Huésped</th>
                    <th style="width: 15%; text-align: right;">Precio</th>
                </tr>
            </thead>
            <tbody>
                @php $subtotal = 0; @endphp
                @foreach($datos['reservas_detalle'] as $index => $reserva)
                @php $subtotal += $reserva['precio']; @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($reserva['check_in'])->format('d/m/Y') }}</td>
                    <td>{{ $reserva['check_out'] ? \Carbon\Carbon::parse($reserva['check_out'])->format('d/m/Y') : 'N/A' }}</td>
                    <td style="text-align: center;">
                        <span class="badge badge-info">{{ $reserva['noches'] }}</span>
                    </td>
                    @if(!isset($datos['establecimiento']))
                        <td>{{ $reserva['establecimiento'] }}</td>
                    @endif
                    <td>{{ $reserva['huesped'] }}</td>
                    <td style="text-align: right;">
                        <strong class="highlight-number">${{ number_format($reserva['precio'], 0, ',', '.') }}</strong>
                    </td>
                </tr>
                @endforeach
                {{-- Fila de Total --}}
                <tr class="total-row">
                    <td colspan="{{ isset($datos['establecimiento']) ? '5' : '6' }}" style="text-align: right; text-transform: uppercase; letter-spacing: 1px;">
                        💵 Total Ingresos del Período
                    </td>
                    <td style="text-align: right; font-size: 12pt;">
                        ${{ number_format($subtotal, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="detalle-note">
            <strong>📌 Nota:</strong> Este detalle muestra todas las reservas confirmadas durante el período analizado, ordenadas por fecha de check-in más reciente. El total coincide exactamente con el valor de "Ingresos Totales" mostrado en el resumen ejecutivo.
        </div>
    </div>
    @endif

    {{-- Salto de página antes de Insights --}}
    <div class="page-break"></div>

    {{-- Insights --}}
    <div class="section">
        <div class="section-title">💡 Análisis e Insights</div>

        @foreach($insights as $insight)
        <div class="insight-box">
            <div class="insight-icon">{{ $insight['icono'] }}</div>
            <div class="insight-content">
                <h4>{{ $insight['titulo'] }}</h4>
                <div class="insight-value">{{ $insight['valor'] }}</div>
                <div class="insight-desc">{{ $insight['descripcion'] }}</div>
            </div>
            <div style="clear: both;"></div>
        </div>
        @endforeach
    </div>

    {{-- Gráfica de Tendencias --}}
    <div class="section">
        <div class="section-title">📊 Tendencia de Reservas</div>

        <div class="chart-container">
            <div class="chart-title">Evolución Mensual de Reservas</div>
            @php
                $maxValor = max($datos['tendencias']['valores']);
                $chartHeight = 180;
            @endphp

            <div style="display: flex; align-items: flex-end; justify-content: space-around; height: {{ $chartHeight }}px; border-bottom: 3px solid #667eea; padding: 0 10px;">
                @foreach($datos['tendencias']['valores'] as $index => $valor)
                    @php
                        $altura = $maxValor > 0 ? ($valor / $maxValor) * ($chartHeight - 30) : 0;
                    @endphp
                    <div style="text-align: center; flex: 1;">
                        <div style="background-color: #7c3aed; width: 85%; height: {{ $altura }}px; margin: 0 auto; border-radius: 6px 6px 0 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div style="color: #ffffff; font-size: 10pt; padding-top: 8px; font-weight: bold;">{{ $valor }}</div>
                        </div>
                        <div style="font-size: 8pt; margin-top: 10px; color: #4a5568; font-weight: 600;">{{ $datos['tendencias']['labels'][$index] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Salto de página antes de Top Propiedades --}}
    <div class="page-break"></div>

    {{-- Top Propiedades --}}
    <div class="section">
        @if(isset($datos['establecimiento']) && $datos['establecimiento'])
            <div class="section-title">🏢 Información de la Propiedad</div>
        @else
            <div class="section-title">🏆 Top 10 Propiedades</div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Propiedad</th>
                    <th style="width: 25%;">Propietario</th>
                    <th style="width: 15%; text-align: center;">Reservas</th>
                    <th style="width: 20%;">RNT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos['top_propiedades'] as $index => $propiedad)
                <tr>
                    <td style="text-align: center;"><strong>{{ $index + 1 }}</strong></td>
                    <td><strong>{{ $propiedad['nombre'] }}</strong></td>
                    <td>{{ $propiedad['propietario'] }}</td>
                    <td style="text-align: center;">
                        <span class="badge badge-success">{{ number_format($propiedad['reservas']) }}</span>
                    </td>
                    <td>{{ $propiedad['rnt'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Desglose de Reservas por Estado --}}
    <div class="section">
        <div class="section-title">🎫 Análisis de Reservas</div>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50%;">Categoría</th>
                    <th style="width: 25%; text-align: right;">Cantidad</th>
                    <th style="width: 25%; text-align: right;">% del Total</th>
                </tr>
            </thead>
            <tbody>
                @php $totalReservas = $datos['reservas']['total']; @endphp
                <tr>
                    <td><strong>Total de Reservas</strong></td>
                    <td style="text-align: right;"><strong class="highlight-number">{{ number_format($totalReservas) }}</strong></td>
                    <td style="text-align: right;"><strong>100%</strong></td>
                </tr>
                <tr>
                    <td>Reservas Activas</td>
                    <td style="text-align: right;">{{ number_format($datos['reservas']['activas']) }}</td>
                    <td style="text-align: right;">{{ $totalReservas > 0 ? number_format(($datos['reservas']['activas'] / $totalReservas) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Reservas Completadas</td>
                    <td style="text-align: right;">{{ number_format($datos['reservas']['completadas']) }}</td>
                    <td style="text-align: right;">{{ $totalReservas > 0 ? number_format(($datos['reservas']['completadas'] / $totalReservas) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Reservas Futuras</td>
                    <td style="text-align: right;">{{ number_format($datos['reservas']['futuras']) }}</td>
                    <td style="text-align: right;">{{ $totalReservas > 0 ? number_format(($datos['reservas']['futuras'] / $totalReservas) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>Con Seguro Contratado</td>
                    <td style="text-align: right;">{{ number_format($datos['reservas']['con_seguro']) }}</td>
                    <td style="text-align: right;">{{ $totalReservas > 0 ? number_format(($datos['reservas']['con_seguro'] / $totalReservas) * 100, 1) : 0 }}%</td>
                </tr>
                <tr>
                    <td>TRA Enviados</td>
                    <td style="text-align: right;">{{ number_format($datos['reservas']['tra_enviados']) }}</td>
                    <td style="text-align: right;">{{ $totalReservas > 0 ? number_format(($datos['reservas']['tra_enviados'] / $totalReservas) * 100, 1) : 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Información de Establecimientos --}}
    <div class="section">
        <div class="section-title">🏢 Estado de Establecimientos</div>

        <div class="stats-grid">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value success">{{ number_format($datos['establecimientos']['total_activos']) }}</div>
                    <div class="stat-label">Activos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value info">{{ number_format($datos['establecimientos']['con_auto_tra']) }}</div>
                    <div class="stat-label">Con Auto TRA</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value warning">{{ number_format($datos['establecimientos']['con_reservas_en_periodo']) }}</div>
                    <div class="stat-label">Con Reservas</div>
                </div>
            </div>
        </div>

        <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
            <strong style="color: #4a5568;">Tasa de Automatización TRA:</strong>
            <span style="font-size: 16pt; color: #667eea; font-weight: bold; margin-left: 10px;">
                {{ $datos['establecimientos']['total_activos'] > 0 ? number_format(($datos['establecimientos']['con_auto_tra'] / $datos['establecimientos']['total_activos']) * 100, 1) : 0 }}%
            </span>
        </div>
    </div>

    {{-- Gastos Operacionales --}}
    @if($datos['gastos']['tiene_gastos'])
    <div class="page-break"></div>

    <div class="section">
        <div class="section-title" style="background-color: #16a34a; color: #ffffff;">
            💰 Gastos Operacionales
        </div>

        {{-- Resumen de Totales --}}
        <table style="width: 100%; margin-bottom: 20px; border-spacing: 10px;">
            <tr>
                <td style="width: 33.33%; background-color: #f7fafc; border: 2px solid #dc2626; padding: 20px; text-align: center; border-radius: 10px;">
                    <div style="font-size: 26pt; font-weight: bold; color: #dc2626; margin-bottom: 8px;">
                        ${{ number_format($datos['gastos']['total_aseo'], 0, ',', '.') }}
                    </div>
                    <div style="font-size: 9pt; color: #718096; font-weight: 600; text-transform: uppercase;">Total Aseo</div>
                </td>
                <td style="width: 33.33%; background-color: #f7fafc; border: 2px solid #ea580c; padding: 20px; text-align: center; border-radius: 10px;">
                    <div style="font-size: 26pt; font-weight: bold; color: #ea580c; margin-bottom: 8px;">
                        ${{ number_format($datos['gastos']['total_administracion'], 0, ',', '.') }}
                    </div>
                    <div style="font-size: 9pt; color: #718096; font-weight: 600; text-transform: uppercase;">Administración</div>
                </td>
                <td style="width: 33.33%; background-color: #f7fafc; border: 2px solid #ca8a04; padding: 20px; text-align: center; border-radius: 10px;">
                    <div style="font-size: 26pt; font-weight: bold; color: #ca8a04; margin-bottom: 8px;">
                        ${{ number_format($datos['gastos']['total_otros'], 0, ',', '.') }}
                    </div>
                    <div style="font-size: 9pt; color: #718096; font-weight: 600; text-transform: uppercase;">Otros Gastos</div>
                </td>
            </tr>
        </table>

        <div style="background-color: #fee2e2; border: 3px solid #dc2626; padding: 20px; margin-bottom: 25px; text-align: center; border-radius: 10px;">
            <div style="font-size: 32pt; font-weight: bold; color: #991b1b; margin-bottom: 8px;">
                ${{ number_format($datos['gastos']['total_gastos'], 0, ',', '.') }}
            </div>
            <div style="font-size: 10pt; color: #991b1b; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">
                TOTAL GASTOS DEL PERÍODO
            </div>
        </div>

        {{-- Detalle por Mes --}}
        @if(count($datos['gastos']['gastos_por_mes']) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 25%;">Período</th>
                    <th style="width: 20%; text-align: right;">Aseo</th>
                    <th style="width: 20%; text-align: right;">Administración</th>
                    <th style="width: 20%; text-align: right;">Otros</th>
                    <th style="width: 15%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($datos['gastos']['gastos_por_mes'] as $gasto)
                <tr>
                    <td><strong>{{ $gasto['periodo'] }}</strong></td>
                    <td style="text-align: right;">${{ number_format($gasto['aseo'], 0, ',', '.') }}</td>
                    <td style="text-align: right;">${{ number_format($gasto['administracion'], 0, ',', '.') }}</td>
                    <td style="text-align: right;">${{ number_format($gasto['otros_gastos'], 0, ',', '.') }}</td>
                    <td style="text-align: right;"><strong class="highlight-number">${{ number_format($gasto['total'], 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Balance Financiero --}}
        <div style="background-color: #eff6ff; border: 3px solid #3b82f6; padding: 20px; margin-top: 25px; border-radius: 10px;">
            <h3 style="font-size: 13pt; font-weight: bold; color: #1e40af; margin-bottom: 15px; text-align: center;">
                📊 Balance Financiero del Período
            </h3>

            <table style="width: 100%; border-spacing: 10px;">
                <tr>
                    <td style="width: 33.33%; background-color: #ffffff; border: 2px solid #16a34a; padding: 20px; text-align: center; border-radius: 10px;">
                        <div style="font-size: 9pt; color: #4b5563; margin-bottom: 5px; font-weight: 600;">INGRESOS TOTALES</div>
                        <div style="font-size: 20pt; font-weight: bold; color: #16a34a;">
                            ${{ number_format($datos['estadisticas_generales']['total_ingresos'], 0, ',', '.') }}
                        </div>
                    </td>

                    <td style="width: 33.33%; background-color: #ffffff; border: 2px solid #dc2626; padding: 20px; text-align: center; border-radius: 10px;">
                        <div style="font-size: 9pt; color: #4b5563; margin-bottom: 5px; font-weight: 600;">GASTOS TOTALES</div>
                        <div style="font-size: 20pt; font-weight: bold; color: #dc2626;">
                            -${{ number_format($datos['gastos']['total_gastos'], 0, ',', '.') }}
                        </div>
                    </td>

                    <td style="width: 33.33%; background-color: #ffffff; border: 3px solid #2563eb; padding: 20px; text-align: center; border-radius: 10px;">
                        <div style="font-size: 9pt; color: #4b5563; margin-bottom: 5px; font-weight: 600;">BALANCE NETO</div>
                        <div style="font-size: 24pt; font-weight: bold; color: #1d4ed8;">
                            ${{ number_format($datos['estadisticas_generales']['total_ingresos'] - $datos['gastos']['total_gastos'], 0, ',', '.') }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Notas --}}
        @if(count($datos['gastos']['notas']) > 0)
        <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; margin-top: 20px; border-radius: 6px;">
            <h4 style="font-weight: bold; color: #92400e; font-size: 10pt; margin-bottom: 8px;">📝 Notas de Gastos:</h4>
            <ul style="margin-left: 15px; font-size: 9pt; color: #4a5568; line-height: 1.6;">
                @foreach($datos['gastos']['notas'] as $nota)
                    <li style="margin-bottom: 4px;">{{ $nota }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p><strong>Digitra Analytics</strong></p>
        <p>Sistema Profesional de Análisis de Datos para Propiedades Turísticas</p>
        <p>Informe generado el {{ now()->format('d/m/Y') }} a las {{ now()->format('H:i') }}</p>
        <p style="margin-top: 15px; font-size: 7pt; color: #a0aec0;">
            📋 Este documento contiene información confidencial. Uso exclusivo para análisis interno y toma de decisiones estratégicas.
        </p>
    </div>
</body>
</html>
