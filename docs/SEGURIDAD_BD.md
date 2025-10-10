# 🔒 SEGURIDAD DE BASE DE DATOS - DIGITRA ANALYTICS

## ⚠️ IMPORTANTE: PROTECCIÓN DE LA BD DE PRODUCCIÓN

Este sistema de analytics **NUNCA** debe modificar la base de datos de Digitra.rent en producción.

---

## 🛡️ CAPA 1: Usuario MySQL de Solo Lectura (RECOMENDADO)

### Paso 1: Crear usuario de solo lectura

Conectarse al servidor MySQL de Digitra y ejecutar:

```sql
-- Crear usuario de solo lectura
CREATE USER 'digitra_readonly'@'%' IDENTIFIED BY 'PasswordSeguro2024!';

-- Otorgar SOLO permisos de lectura (SELECT)
GRANT SELECT ON digiroot_digitra.* TO 'digitra_readonly'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar permisos
SHOW GRANTS FOR 'digitra_readonly'@'%';
```

### Paso 2: Actualizar .env

```env
DIGITRA_DB_USERNAME=digitra_readonly
DIGITRA_DB_PASSWORD=PasswordSeguro2024!
```

### Ventajas:
- ✅ **Imposible** hacer INSERT, UPDATE, DELETE desde Laravel
- ✅ Protección a nivel de MySQL (más segura)
- ✅ Si alguien intenta escribir, MySQL rechaza la operación
- ✅ Auditable desde MySQL logs

---

## 🛡️ CAPA 2: Modelos Eloquent de Solo Lectura

Los modelos de Digitra están configurados con protecciones:

```php
// Todos los modelos en App\Models\Digitra\* tienen:
protected $connection = 'mysql'; // Conexión separada
protected $guarded = ['*'];      // No permite mass-assignment
```

---

## 🛡️ CAPA 3: Observer para Bloquear Escrituras (Failsafe)

Si por alguna razón se intenta escribir, un Observer lo bloqueará:

```php
// En app/Observers/ReadOnlyObserver.php
public function creating($model) {
    throw new \Exception('NO SE PERMITE CREAR registros en la BD de Digitra!');
}
```

---

## ✅ Verificación de Seguridad

### Test 1: Intentar crear un registro
```bash
php artisan tinker
>>> App\Models\Digitra\Establecimiento::create(['nombre' => 'test'])
# Debería FALLAR con error de permisos
```

### Test 2: Verificar conexión de solo lectura
```bash
php artisan tinker
>>> DB::connection('mysql')->select('SELECT 1')
# ✅ Funciona (lectura)
>>> DB::connection('mysql')->insert('INSERT INTO users VALUES ()')
# ❌ Error: Access denied
```

---

## 📊 Diagrama de Conexiones

```
┌─────────────────────────────────────┐
│   DIGITRA ANALYTICS (Laravel)       │
├─────────────────────────────────────┤
│                                     │
│  SQLite (DB_CONNECTION=sqlite)     │ ← Escritura/Lectura
│  ├─ users (admin)                   │   (Sistema local)
│  ├─ sessions                        │
│  ├─ cache                           │
│  └─ jobs                            │
│                                     │
│  MySQL Digitra (connection=mysql)   │ ← SOLO LECTURA
│  ├─ users (digitra)                 │   (Usuario: digitra_readonly)
│  ├─ establecimientos                │
│  ├─ reservas                        │
│  └─ huespedes                       │
│                                     │
└─────────────────────────────────────┘
```

---

## 🚨 Checklist de Seguridad

- [ ] Crear usuario `digitra_readonly` en MySQL
- [ ] Actualizar `.env` con nuevo usuario
- [ ] Configurar Observer de solo lectura
- [ ] Ejecutar tests de verificación
- [ ] Monitorear logs de acceso
- [ ] Documentar accesos

---

## 📝 Logs y Auditoría

Para auditar accesos a Digitra BD:

```php
// En config/database.php
'mysql' => [
    // ...
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => true,
    ],
    'dump' => [
        'log_queries' => true, // Log todas las queries
    ],
],
```

---

**Última actualización**: 2025-10-08
**Responsable**: Admin Digitra Analytics
