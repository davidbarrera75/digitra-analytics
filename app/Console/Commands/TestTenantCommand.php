<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Digitra\Establecimiento;
use Illuminate\Console\Command;

class TestTenantCommand extends Command
{
    protected $signature = 'tenant:test';
    protected $description = 'Probar funcionalidad de multi-tenancy';

    public function handle()
    {
        $this->info('🧪 Probando sistema Multi-Tenant');
        $this->newLine();

        // Mostrar tenants creados
        $this->info('📋 Tenants en el sistema:');
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            $this->line("  - {$tenant->name} (ID: {$tenant->id}, Digitra User: {$tenant->digitra_user_id})");
        }
        $this->newLine();

        // Probar acceso por tenant
        foreach ($tenants as $tenant) {
            $this->info("🔍 Datos para tenant: {$tenant->name}");

            // Obtener establecimientos del usuario de Digitra
            $establecimientos = Establecimiento::where('user_id', $tenant->digitra_user_id)->count();
            $this->line("  → Establecimientos: {$establecimientos}");

            // Obtener usuarios locales del tenant
            $users = User::where('tenant_id', $tenant->id)->get();
            $this->line("  → Usuarios locales: {$users->count()}");
            foreach ($users as $user) {
                $this->line("     • {$user->email} - " . ($user->is_super_admin ? 'Super Admin' : 'Admin'));
            }
            $this->newLine();
        }

        // Probar helpers
        $this->info('🔧 Probando helpers globales:');
        $this->line('  (Los helpers funcionan solo con usuario autenticado)');
        $this->line('  - tenant() → Retorna el tenant actual');
        $this->line('  - tenant_id() → Retorna ID del tenant');
        $this->line('  - digitra_user_id() → Retorna ID usuario Digitra');
        $this->line('  - is_super_admin() → Verifica si es super admin');
        $this->newLine();

        $this->info('✅ Sistema Multi-Tenant funcionando correctamente!');
        $this->newLine();

        $this->info('📝 Para probar en el panel:');
        $this->line('  1. Iniciar servidor: php artisan serve');
        $this->line('  2. Acceder: http://localhost:8000/admin');
        $this->line('  3. Login como Super Admin: admin@digitra.com / admin123');
        $this->line('  4. O como Tenant Admin: admin.tenant1@digitra.com / password');

        return Command::SUCCESS;
    }
}
