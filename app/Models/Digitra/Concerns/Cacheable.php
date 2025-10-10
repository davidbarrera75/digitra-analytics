<?php

namespace App\Models\Digitra\Concerns;

use Illuminate\Support\Facades\Cache;

trait Cacheable
{
    /**
     * Tiempo de caché por defecto: 5 minutos (300 segundos)
     */
    protected static int $defaultCacheTtl = 300;

    /**
     * Cachear el resultado de un query
     *
     * @param string $key Clave única para el caché
     * @param \Closure $callback Callback que retorna el query
     * @param int|null $ttl Tiempo en segundos (null = usar default)
     * @return mixed
     */
    public static function cacheQuery(string $key, \Closure $callback, ?int $ttl = null)
    {
        $ttl = $ttl ?? static::$defaultCacheTtl;
        $cacheKey = 'digitra_' . static::class . '_' . $key;

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * Invalidar caché para esta entidad
     *
     * @param string|null $key Clave específica o null para invalidar todo
     * @return void
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key) {
            $cacheKey = 'digitra_' . static::class . '_' . $key;
            Cache::forget($cacheKey);
        } else {
            // Invalidar todas las claves relacionadas con este modelo
            Cache::flush(); // En producción, usar tags de Redis para mayor precisión
        }
    }
}
