# 🤖 Feature: Resúmenes Mensuales con IA

## Descripción

Sistema de generación automática de resúmenes mensuales usando **Claude AI** (Anthropic). Cada usuario recibe un análisis personalizado de su negocio con insights, recomendaciones y comparaciones históricas.

---

## 💰 Costos

### Por Usuario/Mes:
- **Resumen Simple**: $0.03 USD/mes (~2,500 tokens input + 1,500 output)
- **Resumen Extendido**: $0.05 USD/mes (~4,000 tokens input + 2,500 output)

### Proyección:
| Usuarios | Costo Mensual | Costo Anual |
|----------|---------------|-------------|
| 50       | $1.50 - $2.50 | $18 - $30   |
| 100      | $3 - $5       | $36 - $60   |
| 500      | $15 - $25     | $180 - $300 |
| 1,000    | $30 - $50     | $360 - $600 |

---

## 🎯 Características

### 1. **Generación Automática**
- Se ejecuta el día 1 de cada mes
- Analiza datos del mes anterior
- Genera resumen en menos de 10 segundos

### 2. **Contenido del Resumen**
- ✅ Saludo personalizado
- 📊 Resumen ejecutivo
- 💡 Insights clave (3-5 hallazgos)
- 🎯 Recomendaciones accionables (3-4 sugerencias)
- 📈 Comparación mensual con período anterior
- 🔮 Perspectiva para el próximo mes

### 3. **Datos Analizados**
- Ingresos totales
- Gastos operativos
- Utilidad neta
- Número de reservas
- Noches reservadas
- Porcentaje de ocupación
- Precio promedio por noche
- Comparación con mes anterior

### 4. **Widget en Dashboard**
- Diseño atractivo con gradientes
- Formato markdown renderizado
- Badge de estado actualizado
- Información de tokens usados
- Estado vacío informativo

---

## 🛠️ Instalación y Configuración

### 1. **Obtener API Key de Claude**

1. Ve a: https://console.anthropic.com/
2. Regístrate o inicia sesión
3. Ve a "API Keys"
4. Crea una nueva API Key
5. Copia la key (empieza con `sk-ant-`)

### 2. **Configurar Variables de Entorno**

Agrega a tu archivo `.env`:

```bash
# Claude AI API
CLAUDE_API_KEY=sk-ant-api03-tu-clave-aqui
CLAUDE_MODEL=claude-3-5-sonnet-20241022
```

### 3. **Ejecutar Migración**

```bash
php artisan migrate
```

Esto creará la tabla `resumenes_mensuales_ia` con los siguientes campos:
- `id`
- `user_id`
- `mes` (1-12)
- `año`
- `contenido` (texto markdown)
- `datos_estadisticos` (JSON)
- `tokens_usados`
- `generado_en`
- `created_at` / `updated_at`

---

## 📝 Uso

### Generar Resumen Manualmente

#### Para un usuario específico:
```bash
php artisan resumen:generar --user=1 --mes=10 --año=2024
```

#### Para todos los usuarios:
```bash
php artisan resumen:generar --todos --mes=10 --año=2024
```

#### Para el mes anterior (por defecto):
```bash
php artisan resumen:generar --todos
```

### Automatización (Cron)

Agrega a tu `crontab` o scheduler:

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Generar resúmenes el día 1 de cada mes a las 2 AM
    $schedule->command('resumen:generar --todos')
        ->monthlyOn(1, '02:00')
        ->onOneServer()
        ->withoutOverlapping();
}
```

---

## 🎨 Ejemplo de Resumen Generado

```markdown
## Hola David! 👋

Tuviste un mes **sobresaliente**. Con $4,250,000 COP en ingresos,
superaste septiembre en un 12%. Tu ocupación del 74% está por encima
del promedio del mercado (65%) para esta temporada.

### 💡 Insights Clave

• **🌟 Mejor Semana del Año**: Del 7-13 octubre generaste $1.2M,
  probablemente por el puente festivo.

• **🏆 Propiedad Estrella**: Tu "Casa Campestre" tuvo 18 noches
  reservadas (95% ocupación). Considera aumentar precio 10%.

• **💰 Optimización de Gastos**: Redujiste gastos operativos 8%
  vs septiembre. Excelente control de costos.

### 🎯 Recomendaciones para Noviembre

1. **Ajuste de Precios**: Baja precios entre semana 15% pero mantén
   fines de semana. Potencial: +$800K en ingresos.

2. **Promo Early Bird**: Ofrece 10% descuento para reservas con
   15+ días de anticipación.

### 📊 Comparación con Septiembre

| Métrica | Octubre | Septiembre | Cambio |
|---------|---------|------------|--------|
| Ingresos | $4.25M | $3.8M | +12% 🟢 |
| Noches | 23 | 19 | +21% 🟢 |
| Ocupación | 74% | 61% | +13pp 🟢 |
```

---

## 🔧 Arquitectura Técnica

### Componentes:

1. **ClaudeService** (`app/Services/ClaudeService.php`)
   - Comunicación con API de Anthropic
   - Construcción de prompts optimizados
   - Manejo de errores y logging

2. **GenerarResumenMensualIA Job** (`app/Jobs/GenerarResumenMensualIA.php`)
   - Queue job para procesamiento asíncrono
   - Recopilación de datos del usuario
   - Cálculo de estadísticas
   - Llamada al servicio de Claude
   - Almacenamiento en BD

3. **ResumenMensualIA Model** (`app/Models/ResumenMensualIA.php`)
   - Modelo Eloquent
   - Casteo de JSON
   - Helpers para fechas y formato
   - Scopes útiles

4. **ResumenMensualIAWidget** (`app/Filament/Widgets/ResumenMensualIAWidget.php`)
   - Widget de Filament
   - Renderizado en dashboard
   - Vista con diseño atractivo

5. **GenerarResumenesIA Command** (`app/Console/Commands/GenerarResumenesIA.php`)
   - Comando artisan
   - Generación manual
   - Progress bar para múltiples usuarios

### Flujo de Datos:

```
Usuario → Tenant → Digitra User ID
         ↓
    Reservas (MySQL)
    Gastos (SQLite)
         ↓
    Recopilación de Datos
         ↓
    Claude API (HTTP)
         ↓
    Resumen Generado
         ↓
    BD SQLite (resumenes_mensuales_ia)
         ↓
    Widget Dashboard
```

---

## 🔐 Seguridad y Privacidad

### Datos Enviados a Claude:
- ✅ Estadísticas agregadas (ingresos, reservas, ocupación)
- ✅ Nombres de propiedades
- ✅ Nombre del usuario
- ❌ NO se envían datos de huéspedes
- ❌ NO se envían datos de contacto
- ❌ NO se envían números de documentos

### Almacenamiento:
- Los resúmenes se guardan en tu base de datos local (SQLite)
- No se comparten con terceros
- El usuario puede ver su historial completo

---

## 📊 Monitoreo y Costos

### Ver Tokens Usados:

```php
use App\Models\ResumenMensualIA;

// Total de tokens del mes
$totalTokens = ResumenMensualIA::whereMonth('created_at', now()->month)
    ->sum('tokens_usados');

// Costo aproximado
$costoInput = ($totalTokens * 0.6) * (3 / 1000000); // 60% input
$costoOutput = ($totalTokens * 0.4) * (15 / 1000000); // 40% output
$costoTotal = $costoInput + $costoOutput;

echo "Tokens: {$totalTokens}\n";
echo "Costo estimado: $" . number_format($costoTotal, 4) . " USD\n";
```

---

## 🚀 Próximas Mejoras (Roadmap)

### Fase 2 (Mes 2-3):
- [ ] Email automático con el resumen
- [ ] Opción de regenerar resumen
- [ ] Resúmenes semanales (opcional)
- [ ] Comparación con promedio del mercado

### Fase 3 (Mes 4+):
- [ ] Chat interactivo (5 preguntas gratis/mes)
- [ ] Alertas proactivas
- [ ] Predicciones de ocupación
- [ ] Recomendaciones de pricing dinámico

---

## 🐛 Troubleshooting

### Problema: "Error al comunicarse con Claude API"

**Solución**:
1. Verifica que tu API key es válida
2. Revisa los logs: `storage/logs/laravel.log`
3. Verifica conectividad a internet
4. Comprueba límites de rate de Anthropic

### Problema: "No se genera resumen para un usuario"

**Solución**:
1. Verifica que el usuario tenga un tenant configurado
2. Verifica que tenga reservas en el período
3. Revisa los logs del job: `php artisan queue:failed`

### Problema: "Widget no aparece en dashboard"

**Solución**:
1. Limpia cache: `php artisan cache:clear`
2. Verifica permisos del usuario
3. Verifica que el widget esté registrado en el panel

---

## 📞 Soporte

Para más información o soporte, contacta al equipo de desarrollo.

---

**Generado con 🤖 Claude AI**
