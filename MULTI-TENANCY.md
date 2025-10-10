# 🏢 Sistema Multi-Tenant - Digitra Analytics

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Modelos y Relaciones](#modelos-y-relaciones)
4. [Aislamiento de Datos](#aislamiento-de-datos)
5. [Roles y Permisos](#roles-y-permisos)
6. [Sistema de Informes](#sistema-de-informes)
7. [Selector de Tenant (Super Admin)](#selector-de-tenant-super-admin)
8. [Cambio de Contraseña](#cambio-de-contraseña)
9. [Guía de Uso](#guía-de-uso)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)

---

## Introducción

Digitra Analytics es un sistema multi-tenant que permite a múltiples usuarios (tenants) gestionar sus propios establecimientos, reservas y generar informes de manera completamente aislada.

### ✨ Características Principales

- 🔐 **Aislamiento Completo de Datos**: Cada tenant solo ve sus propios datos
- 👥 **239 Cuentas Creadas**: Una cuenta por cada usuario de Digitra con establecimientos
- 📊 **Informes PDF Personalizados**: Generales o por establecimiento individual
- 🔄 **Selector de Tenant para Super Admin**: Visualizar datos de cualquier tenant
- 🔑 **Cambio de Contraseña**: Los usuarios pueden actualizar su contraseña
- ✅ **Tests Automatizados**: Verificación del aislamiento de datos
- ⚡ **Cache Inteligente**: Aislado por tenant para optimizar rendimiento

---

## Arquitectura del Sistema

### Estructura Multi-Tenant

```
┌─────────────────────────────────────────────────────────────┐
│                      SUPER ADMIN                            │
│  (puede ver todos los tenants o filtrar por uno específico) │
└─────────────────────────────────────────────────────────────┘
                             │
         ┌───────────────────┼───────────────────┐
         │                   │                   │
    ┌────────┐          ┌────────┐          ┌────────┐
    │TENANT 1│          │TENANT 2│          │TENANT N│
    └────────┘          └────────┘          └────────┘
         │                   │                   │
    ┌─────────┐         ┌─────────┐         ┌─────────┐
    │USUARIOS │         │USUARIOS │         │USUARIOS │
    └─────────┘         └─────────┘         └─────────┘
         │                   │                   │
    ┌────────────┐      ┌────────────┐      ┌────────────┐
    │ESTABLEC.   │      │ESTABLEC.   │      │ESTABLEC.   │
    │RESERVAS    │      │RESERVAS    │      │RESERVAS    │
    │HUÉSPEDES   │      │HUÉSPEDES   │      │HUÉSPEDES   │
    └────────────┘      └────────────┘      └────────────┘
```

### Bases de Datos

- **SQLite (local)**: Almacena tenants y usuarios locales
- **MySQL (remoto)**: Base de datos de Digitra (solo lectura)

---

## Modelos y Relaciones

### Modelo Tenant

**Ubicación**: `app/Models/Tenant.php`

```php
class Tenant extends Model
{
    // Campos
    - id
    - name
    - slug (único)
    - digitra_user_id (único, relación con Digitra User)
    - email
    - phone
    - is_active
    - settings (JSON)
    - trial_ends_at
    
    // Relaciones
    - users() -> hasMany(User)
    - digitraUser() -> belongsTo(DigitraUser)
}
```

### Modelo User (Local)

**Ubicación**: `app/Models/User.php`

```php
class User extends Model
{
    // Campos
    - id
    - name
    - email
    - password
    - tenant_id
    - is_super_admin
    
    // Métodos importantes
    - isSuperAdmin(): bool
    - getCurrentTenant(): ?Tenant
    - canViewAllTenants(): bool
}
```

---

## Aislamiento de Datos

### Global Scopes

**Ubicación**: `app/Models/Scopes/TenantScope.php`

El sistema utiliza Global Scopes de Eloquent para filtrar automáticamente los datos:

```php
public function apply(Builder $builder, Model $model): void
{
    if (auth()->check()) {
        $user = auth()->user();
        
        // Super admin sin tenant activo = ver todo
        if ($user->isSuperAdmin() && !session()->has('active_tenant_id')) {
            return;
        }
        
        // Filtrar por tenant
        if ($user->tenant_id) {
            $builder->where('tenant_id', $user->tenant_id);
        }
    }
}
```

### Trait BelongsToTenant

**Ubicación**: `app/Models/Concerns/BelongsToTenant.php`

Los modelos que usan este trait automáticamente:
- Aplican el TenantScope
- Asignan el tenant_id al crear registros
- Proveen métodos útiles: `withAllTenants()`, `forTenant()`

### Helpers Globales

**Ubicación**: `app/helpers.php`

```php
tenant()           // Retorna el tenant actual
tenant_id()        // Retorna el ID del tenant actual
digitra_user_id()  // Retorna el digitra_user_id del tenant
is_super_admin()   // Verifica si es super admin
```

---

## Roles y Permisos

### Roles Disponibles

| Rol | Descripción | Permisos |
|-----|-------------|----------|
| **Super Admin** | Administrador global | Ver todos los tenants, cambiar entre tenants, acceso total |
| **Admin** | Administrador del tenant | Ver y gestionar datos de su tenant |
| **Viewer** | Usuario de solo lectura | Ver datos de su tenant (sin editar) |

### Implementación

El sistema usa **Spatie Laravel Permission** para gestionar roles y permisos.

**Asignación de rol**:
```php
$user->assignRole('Admin');
```

---

## Sistema de Informes

### Tipos de Informes

#### 1. Informe General
Muestra datos de **todos los establecimientos** del tenant.

**Características**:
- Estadísticas generales consolidadas
- Top 10 propiedades por reservas
- Tendencias mensuales
- Insights automáticos

#### 2. Informe Individual
Muestra datos de **un establecimiento específico**.

**Características**:
- Header personalizado con nombre del establecimiento y RNT
- Datos filtrados solo para ese establecimiento
- Nombre de archivo: `Informe_NombreEstablecimiento_YYYYMMDD_YYYYMMDD.pdf`

### Seguridad en Informes

**Ubicación**: `app/Services/InformeService.php`

```php
// VALIDACIÓN DE SEGURIDAD
if ($establecimientoId && $digitraUserId) {
    $establecimiento = Establecimiento::find($establecimientoId);
    if (!$establecimiento || $establecimiento->user_id !== $digitraUserId) {
        throw new \Exception('No tienes permiso para acceder a este establecimiento.');
    }
}
```

---

## Selector de Tenant (Super Admin)

### Ubicación
**Componente**: `app/Livewire/TenantSwitcher.php`  
**Vista**: `resources/views/livewire/tenant-switcher.blade.php`

### Funcionalidad

El selector aparece en la barra superior **solo para Super Admins** y permite:

1. **Ver todos los datos** (sin filtro)
   - Seleccionar: "🌐 TODOS los Tenants (Sin Filtro)"
   
2. **Ver datos de un tenant específico**
   - Seleccionar cualquier tenant de la lista
   - Todos los widgets, recursos e informes se filtran automáticamente

### Uso

```
┌──────────────────────────────────────────────────┐
│ Ver como: [🌐 TODOS los Tenants (Sin Filtro) ▼] │
│           [👤 HOSTY HOME (larangoe@yahoo.com)]   │
│           [👤 San Jorge (info@sanjorge.com)]     │
│           [👤 ...]                                │
└──────────────────────────────────────────────────┘
```

Al cambiar de tenant:
- Se limpia el cache automáticamente
- La página se recarga con los nuevos filtros
- Los datos mostrados corresponden al tenant seleccionado

---

## Cambio de Contraseña

### Ubicación
**Página**: `app/Filament/Pages/MiPerfil.php`  
**Ruta**: `/admin/mi-perfil`

### Características

- ✅ Muestra información de la cuenta (nombre, email, organización)
- ✅ Formulario de cambio de contraseña con validaciones:
  - Contraseña actual requerida y verificada
  - Nueva contraseña mínimo 8 caracteres
  - Confirmación de nueva contraseña
  - Campos con opción de "mostrar/ocultar" contraseña
- ✅ Notificaciones de éxito/error
- ✅ Disponible para todos los usuarios (tenants y super admin)

### Uso

1. Ir a **Configuración → Mi Perfil**
2. Completar el formulario:
   - Contraseña Actual
   - Nueva Contraseña
   - Confirmar Nueva Contraseña
3. Click en **"Cambiar Contraseña"**

---

## Guía de Uso

### Para Usuarios Tenant

#### Inicio de Sesión
```
URL: http://127.0.0.1:8003/admin
Email: [tu email en Digitra]
Password: Digitra2025 (por defecto)
```

#### Acceso a Funciones
- **Dashboard**: Ver estadísticas de tus establecimientos
- **Establecimientos**: Listar y ver tus propiedades
- **Reservas**: Ver reservas de tus establecimientos
- **Huéspedes**: Ver huéspedes asociados a tus reservas
- **Generar Informe**: Crear informes PDF (general o por establecimiento)
- **Mi Perfil**: Cambiar contraseña

#### Generar Informe

1. Ir a **Informes → Generar Informe**
2. Seleccionar tipo:
   - "📊 Informe General" (todos tus establecimientos)
   - O seleccionar un establecimiento específico
3. Configurar fechas (por defecto: últimos 3 meses)
4. Click en **"Generar PDF"**

### Para Super Admin

#### Funcionalidades Adicionales

- **Ver Usuarios Digitra**: Acceso a la lista completa de usuarios
- **Selector de Tenant**: Cambiar vista entre tenants
- **Widgets Exclusivos**:
  - Aniversarios de Establecimientos
  - Top 10 Propiedades
  - Establecimientos que Cumplen 1 Año

#### Cambiar de Tenant

1. En la barra superior, usar el selector "Ver como:"
2. Seleccionar el tenant deseado
3. Todos los datos se actualizan automáticamente

---

## Testing

### Ejecutar Tests

```bash
php artisan test --filter=TenantIsolationTest
```

### Tests Implementados

**Archivo**: `tests/Feature/TenantIsolationTest.php`

| Test | Descripción |
|------|-------------|
| `helper_functions_return_correct_tenant_data` | Verifica que los helpers retornen datos correctos |
| `super_admin_helper_returns_true` | Verifica detección de super admin |
| `cache_keys_are_tenant_specific` | Verifica aislamiento de cache |

### Cobertura

✅ Helpers globales  
✅ Identificación de roles  
✅ Aislamiento de cache  
✅ Validación de seguridad en informes  

---

## Troubleshooting

### Problema: Usuario no ve sus establecimientos

**Solución**:
1. Verificar que el usuario tiene un `tenant_id` asignado
2. Verificar que el tenant tiene un `digitra_user_id` correcto
3. Limpiar cache: `php artisan cache:clear`

### Problema: Super Admin no ve el selector de tenants

**Solución**:
1. Verificar que `is_super_admin = true` en la tabla `users`
2. Recargar la página con F5
3. Verificar que el componente Livewire esté registrado en `AdminPanelProvider.php`

### Problema: Error al generar informe

**Posibles causas**:
1. **"No tienes permiso..."**: Estás intentando acceder a un establecimiento de otro tenant
2. **Cache corrupto**: Ejecutar `php artisan cache:clear`
3. **Sin datos**: El establecimiento no tiene reservas en el período seleccionado (el informe mostrará 0)

### Problema: Cambio de contraseña no funciona

**Solución**:
1. Verificar que la contraseña actual sea correcta
2. Verificar que la nueva contraseña tenga mínimo 8 caracteres
3. Verificar que ambas contraseñas coincidan

---

## Información Técnica

### Versiones
- Laravel: 12.x
- Filament: 3.2.x
- PHP: 8.2+
- Spatie Laravel Permission: 6.x

### Archivos Clave

```
app/
├── Models/
│   ├── Tenant.php                    # Modelo de tenant
│   ├── User.php                      # Usuario local
│   ├── Scopes/
│   │   └── TenantScope.php           # Global scope de tenant
│   └── Concerns/
│       └── BelongsToTenant.php       # Trait para modelos
├── Services/
│   └── InformeService.php            # Servicio de informes
├── Filament/
│   ├── Pages/
│   │   ├── GenerarInforme.php        # Página de informes
│   │   └── MiPerfil.php              # Página de perfil
│   ├── Resources/
│   │   ├── EstablecimientoResource.php
│   │   ├── ReservaResource.php
│   │   ├── HuespedResource.php
│   │   └── DigitraUserResource.php
│   └── Widgets/
│       ├── DigitraStatsOverview.php
│       ├── AniversariosStats.php
│       └── TopPropiedadesTable.php
├── Livewire/
│   └── TenantSwitcher.php            # Selector de tenant
├── Helpers/
│   └── TenantHelper.php              # Helper de tenant
└── helpers.php                       # Funciones globales

database/
└── migrations/
    └── 2025_10_09_124209_create_tenants_table.php

tests/
└── Feature/
    └── TenantIsolationTest.php       # Tests de aislamiento
```

---

## Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contactar al equipo de desarrollo.

**Versión**: 1.0.0  
**Fecha**: Octubre 2025  
**Autor**: David Barrera con Claude Code
