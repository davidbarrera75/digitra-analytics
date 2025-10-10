<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Digitra\User as DigitraUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $viewerRole = Role::firstOrCreate(['name' => 'Viewer']);

        // Obtener los primeros 3 usuarios de Digitra para crear tenants
        $digitraUsers = DigitraUser::conEstablecimientos()->take(3)->get();

        if ($digitraUsers->isEmpty()) {
            $this->command->warn('No hay usuarios de Digitra con establecimientos. Usando IDs ficticios.');
            // Crear tenants con IDs ficticios para testing
            $tenantIds = [1, 2, 3];
        } else {
            $tenantIds = $digitraUsers->pluck('id')->toArray();
        }

        $tenants = [];

        // Crear tenants basados en usuarios reales de Digitra
        foreach ($tenantIds as $index => $digitraUserId) {
            $digitraUser = DigitraUser::find($digitraUserId);

            $tenant = Tenant::firstOrCreate(
                ['digitra_user_id' => $digitraUserId],
                [
                    'name' => $digitraUser ? $digitraUser->name : "Tenant Demo {$digitraUserId}",
                    'email' => $digitraUser ? $digitraUser->email : "tenant{$digitraUserId}@digitra.com",
                    'phone' => $digitraUser ? $digitraUser->telefono : null,
                    'is_active' => true,
                    'trial_ends_at' => now()->addDays(30),
                ]
            );

            $tenants[] = $tenant;

            $this->command->info("✓ Tenant creado: {$tenant->name} (ID: {$tenant->id}, Digitra User: {$digitraUserId})");
        }

        // Actualizar o crear el usuario super admin
        $superAdmin = User::where('email', 'admin@digitra.com')->first();

        if ($superAdmin) {
            $superAdmin->update([
                'is_super_admin' => true,
                'tenant_id' => null, // Super admin no tiene tenant asignado
            ]);
            $this->command->info('✓ Usuario admin existente actualizado como Super Admin');
        } else {
            $superAdmin = User::create([
                'name' => 'Super Admin',
                'email' => 'admin@digitra.com',
                'password' => Hash::make('admin123'),
                'is_super_admin' => true,
                'tenant_id' => null,
            ]);
            $this->command->info('✓ Super Admin creado: admin@digitra.com / admin123');
        }

        $superAdmin->assignRole($superAdminRole);

        // Crear un usuario admin para cada tenant
        foreach ($tenants as $index => $tenant) {
            $tenantAdmin = User::firstOrCreate(
                ['email' => "admin.tenant{$tenant->id}@digitra.com"],
                [
                    'name' => "Admin {$tenant->name}",
                    'password' => Hash::make('password'),
                    'tenant_id' => $tenant->id,
                    'is_super_admin' => false,
                ]
            );

            $tenantAdmin->assignRole($adminRole);

            $this->command->info("✓ Admin creado para {$tenant->name}: admin.tenant{$tenant->id}@digitra.com / password");
        }

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('Seeder completado exitosamente!');
        $this->command->info('===========================================');
        $this->command->info('Usuarios creados:');
        $this->command->info('- Super Admin: admin@digitra.com / admin123');
        foreach ($tenants as $tenant) {
            $this->command->info("- Admin Tenant {$tenant->id}: admin.tenant{$tenant->id}@digitra.com / password");
        }
        $this->command->info('===========================================');
    }
}
