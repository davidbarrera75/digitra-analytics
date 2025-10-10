<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class VerificarCacheRedis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:verificar';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar el estado del caché Redis y mostrar estadísticas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Verificación de Caché Redis ===');
        $this->newLine();

        // 1. Verificar conexión a Redis
        try {
            Redis::connection()->ping();
            $this->info('✅ Conexión a Redis: OK');
        } catch (\Exception $e) {
            $this->error('❌ Error al conectar con Redis: ' . $e->getMessage());
            return 1;
        }

        // 2. Verificar que el driver de caché es Redis
        $driver = config('cache.default');
        if ($driver === 'redis') {
            $this->info('✅ Driver de caché configurado: Redis');
        } else {
            $this->warn('⚠️  Driver de caché configurado: ' . $driver . ' (se esperaba Redis)');
        }

        $this->newLine();

        // 3. Obtener información del servidor Redis
        $this->info('📊 Información del servidor Redis:');
        try {
            $info = Redis::connection()->info();

            // Mostrar información relevante
            $this->table(
                ['Métrica', 'Valor'],
                [
                    ['Versión de Redis', $info['redis_version'] ?? 'N/A'],
                    ['Modo', $info['redis_mode'] ?? 'N/A'],
                    ['Uptime (días)', isset($info['uptime_in_days']) ? $info['uptime_in_days'] : 'N/A'],
                    ['Clientes conectados', $info['connected_clients'] ?? 'N/A'],
                    ['Memoria usada', isset($info['used_memory_human']) ? $info['used_memory_human'] : 'N/A'],
                    ['Total de claves', $info['db0'] ?? '0'],
                    ['Total comandos procesados', isset($info['total_commands_processed']) ? number_format($info['total_commands_processed']) : 'N/A'],
                ]
            );
        } catch (\Exception $e) {
            $this->error('Error al obtener información de Redis: ' . $e->getMessage());
        }

        $this->newLine();

        // 4. Buscar claves relacionadas con Digitra
        $this->info('🔍 Claves de caché de Digitra Analytics:');
        try {
            // Obtener todas las claves que contengan "digitra"
            $keys = [];
            $cursor = 0;

            do {
                $result = Redis::connection()->scan($cursor, ['match' => '*digitra*', 'count' => 100]);
                $cursor = $result[0];
                $keys = array_merge($keys, $result[1]);
            } while ($cursor != 0);

            if (empty($keys)) {
                $this->warn('   No se encontraron claves en caché. Visita el dashboard para generar caché.');
            } else {
                $this->info('   Total de claves encontradas: ' . count($keys));
                $this->newLine();

                // Mostrar detalles de cada clave
                $cacheData = [];
                foreach ($keys as $key) {
                    $ttl = Redis::connection()->ttl($key);
                    $ttlFormatted = $ttl > 0 ? gmdate("H:i:s", $ttl) : ($ttl === -1 ? 'Sin expiración' : 'Expirada');

                    $cacheData[] = [
                        'Clave' => str_replace('laravel_cache_', '', $key),
                        'TTL restante' => $ttlFormatted,
                    ];
                }

                $this->table(['Clave', 'TTL restante'], $cacheData);
            }
        } catch (\Exception $e) {
            $this->error('Error al buscar claves: ' . $e->getMessage());
        }

        $this->newLine();

        // 5. Test de escritura/lectura
        $this->info('🧪 Test de escritura/lectura:');
        $testKey = 'test_verificacion_' . time();
        $testValue = 'Digitra Analytics - ' . now()->toDateTimeString();

        try {
            // Escribir
            Cache::put($testKey, $testValue, 60);
            $this->info('   ✅ Escritura exitosa');

            // Leer
            $retrieved = Cache::get($testKey);
            if ($retrieved === $testValue) {
                $this->info('   ✅ Lectura exitosa');
            } else {
                $this->error('   ❌ Error en lectura: valores no coinciden');
            }

            // Limpiar
            Cache::forget($testKey);
            $this->info('   ✅ Eliminación exitosa');
        } catch (\Exception $e) {
            $this->error('   ❌ Error en test: ' . $e->getMessage());
        }

        $this->newLine();

        // 6. Comandos útiles
        $this->info('💡 Comandos útiles:');
        $this->line('   • Limpiar todo el caché: php artisan cache:clear');
        $this->line('   • Reiniciar servidor: php artisan serve');
        $this->line('   • Ver logs de Redis: redis-cli monitor');

        $this->newLine();
        $this->info('✅ Verificación completada');

        return 0;
    }
}
