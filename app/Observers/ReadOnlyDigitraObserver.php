<?php

namespace App\Observers;

use Exception;

/**
 * Observer de Seguridad: Bloquea CUALQUIER escritura en modelos de Digitra
 *
 * Este observer se aplica a todos los modelos en App\Models\Digitra\*
 * y previene operaciones de escritura (create, update, delete)
 */
class ReadOnlyDigitraObserver
{
    /**
     * Bloquear creación de registros
     */
    public function creating($model): bool
    {
        throw new Exception(
            '🚫 OPERACIÓN BLOQUEADA: No se permite CREAR registros en la base de datos de Digitra. ' .
            'Modelo: ' . get_class($model)
        );
    }

    /**
     * Bloquear actualización de registros
     */
    public function updating($model): bool
    {
        throw new Exception(
            '🚫 OPERACIÓN BLOQUEADA: No se permite ACTUALIZAR registros en la base de datos de Digitra. ' .
            'Modelo: ' . get_class($model)
        );
    }

    /**
     * Bloquear eliminación de registros
     */
    public function deleting($model): bool
    {
        throw new Exception(
            '🚫 OPERACIÓN BLOQUEADA: No se permite ELIMINAR registros en la base de datos de Digitra. ' .
            'Modelo: ' . get_class($model)
        );
    }

    /**
     * Bloquear restauración de registros (soft delete)
     */
    public function restoring($model): bool
    {
        throw new Exception(
            '🚫 OPERACIÓN BLOQUEADA: No se permite RESTAURAR registros en la base de datos de Digitra. ' .
            'Modelo: ' . get_class($model)
        );
    }

    /**
     * Bloquear forzar eliminación
     */
    public function forceDeleting($model): bool
    {
        throw new Exception(
            '🚫 OPERACIÓN BLOQUEADA: No se permite FORZAR ELIMINACIÓN de registros en la base de datos de Digitra. ' .
            'Modelo: ' . get_class($model)
        );
    }
}
