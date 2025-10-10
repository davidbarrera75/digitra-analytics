<?php

namespace Tests\Feature;

use App\Models\Digitra\User as DigitraUser;
use App\Models\Digitra\Establecimiento;
use App\Models\Digitra\Reserva;
use App\Models\Digitra\Huesped;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RedisCacheTest extends TestCase
{
    /**
     * Test que el caché de Redis funciona correctamente
     */
    public function test_redis_cache_funciona(): void
    {
        // Limpiar caché antes de empezar
        Cache::flush();

        // Probar que podemos guardar y recuperar datos
        Cache::put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key'));

        // Limpiar
        Cache::forget('test_key');
        $this->assertNull(Cache::get('test_key'));
    }

    /**
     * Test que Cache::remember funciona con modelos de Digitra
     */
    public function test_cache_remember_con_modelos_digitra(): void
    {
        Cache::flush();

        // Primera llamada - debe consultar la BD
        $resultado1 = Cache::remember('test_usuarios_count', 60, function () {
            return DigitraUser::count();
        });

        // Segunda llamada - debe obtener del caché (más rápido)
        $resultado2 = Cache::get('test_usuarios_count');

        $this->assertEquals($resultado1, $resultado2);
        $this->assertIsInt($resultado1);

        Cache::forget('test_usuarios_count');
    }

    /**
     * Test que el trait Cacheable funciona correctamente
     */
    public function test_trait_cacheable_funciona(): void
    {
        Cache::flush();

        // Probar el método cacheQuery del trait
        $resultado = DigitraUser::cacheQuery('test_query', function () {
            return DigitraUser::count();
        }, 60);

        $this->assertIsInt($resultado);

        // Verificar que se guardó en caché
        $cacheKey = 'digitra_' . DigitraUser::class . '_test_query';
        $this->assertNotNull(Cache::get($cacheKey));

        // Limpiar con el método del trait
        DigitraUser::clearCache('test_query');
        $this->assertNull(Cache::get($cacheKey));
    }

    /**
     * Test que los widgets usan caché correctamente
     */
    public function test_widgets_usan_cache(): void
    {
        Cache::flush();

        // Simular la llamada que hace DigitraStatsOverview
        $stats = Cache::remember('digitra_stats_overview', 300, function () {
            return [
                'totalUsuarios' => DigitraUser::conEstablecimientos()->count(),
                'totalPropiedades' => Establecimiento::activos()->count(),
                'totalReservas' => Reserva::activas()->count(),
                'totalHuespedes' => Huesped::distinct('numero_documento')->count(),
            ];
        });

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('totalUsuarios', $stats);
        $this->assertArrayHasKey('totalPropiedades', $stats);
        $this->assertArrayHasKey('totalReservas', $stats);
        $this->assertArrayHasKey('totalHuespedes', $stats);

        // Verificar que está en caché
        $cached = Cache::get('digitra_stats_overview');
        $this->assertEquals($stats, $cached);

        Cache::forget('digitra_stats_overview');
    }

    /**
     * Test que el caché expira correctamente
     */
    public function test_cache_expira_correctamente(): void
    {
        Cache::flush();

        // Guardar con TTL de 1 segundo
        Cache::put('test_ttl', 'valor temporal', 1);
        $this->assertEquals('valor temporal', Cache::get('test_ttl'));

        // Esperar 2 segundos
        sleep(2);

        // El valor debe haber expirado
        $this->assertNull(Cache::get('test_ttl'));
    }

    /**
     * Test que múltiples claves de caché funcionan independientemente
     */
    public function test_multiples_claves_cache_independientes(): void
    {
        Cache::flush();

        // Guardar múltiples valores
        Cache::put('key1', 'value1', 60);
        Cache::put('key2', 'value2', 60);
        Cache::put('key3', 'value3', 60);

        $this->assertEquals('value1', Cache::get('key1'));
        $this->assertEquals('value2', Cache::get('key2'));
        $this->assertEquals('value3', Cache::get('key3'));

        // Eliminar una clave
        Cache::forget('key2');

        // Verificar que las otras siguen
        $this->assertEquals('value1', Cache::get('key1'));
        $this->assertNull(Cache::get('key2'));
        $this->assertEquals('value3', Cache::get('key3'));

        Cache::flush();
    }

    /**
     * Test de rendimiento: caché vs consulta directa
     */
    public function test_cache_es_mas_rapido_que_consulta_directa(): void
    {
        Cache::flush();

        // Primera consulta (sin caché)
        $start1 = microtime(true);
        $resultado1 = Reserva::count();
        $tiempo1 = microtime(true) - $start1;

        // Guardar en caché
        Cache::put('reservas_count', $resultado1, 60);

        // Segunda consulta (con caché)
        $start2 = microtime(true);
        $resultado2 = Cache::get('reservas_count');
        $tiempo2 = microtime(true) - $start2;

        // El caché debe ser al menos 10x más rápido
        $this->assertTrue($tiempo2 < $tiempo1 / 10);
        $this->assertEquals($resultado1, $resultado2);

        Cache::forget('reservas_count');
    }
}
