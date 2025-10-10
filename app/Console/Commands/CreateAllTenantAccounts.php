<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Digitra\User as DigitraUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateAllTenantAccounts extends Command
{
    protected $signature = 'tenants:create-all {--password=Digitra2025}';
    protected $description = 'Crear cuentas para todos los usuarios de Digitra con establecimientos';

    public function handle()
    {
        $this->info('🚀 Creando cuentas para todos los usuarios de Digitra...');
        $this->newLine();

        // Obtener password
        $defaultPassword = $this->option('password');
        $this->info("Password por defecto: {$defaultPassword}");
        $this->newLine();

        // Asegurar que existe el rol Admin
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // Obtener todos los usuarios de Digitra con establecimientos
        $digitraUsers = DigitraUser::conEstablecimientos()->get();

        if ($digitraUsers->isEmpty()) {
            $this->error('❌ No se encontraron usuarios de Digitra con establecimientos.');
            return Command::FAILURE;
        }

        $this->info("📊 Usuarios encontrados: {$digitraUsers->count()}");
        $this->newLine();

        $bar = $this->output->createProgressBar($digitraUsers->count());
        $bar->start();

        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($digitraUsers as $digitraUser) {
            try {
                // Verificar si ya existe un tenant para este usuario
                $existingTenant = Tenant::where('digitra_user_id', $digitraUser->id)->first();

                if ($existingTenant) {
                    // Tenant ya existe, verificar si tiene usuario
                    $existingUser = User::where('tenant_id', $existingTenant->id)->first();

                    if ($existingUser) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }
                } else {
                    // Crear nuevo tenant
                    $existingTenant = Tenant::create([
                        'name' => $digitraUser->name,
                        'digitra_user_id' => $digitraUser->id,
                        'email' => $digitraUser->email,
                        'phone' => $digitraUser->telefono,
                        'is_active' => true,
                        'trial_ends_at' => now()->addDays(30),
                    ]);
                }

                // Crear usuario local para el tenant
                $user = User::create([
                    'name' => $digitraUser->name,
                    'email' => $digitraUser->email,
                    'password' => Hash::make($defaultPassword),
                    'tenant_id' => $existingTenant->id,
                    'is_super_admin' => false,
                ]);

                $user->assignRole($adminRole);
                $created++;

            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error con usuario {$digitraUser->email}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Resumen
        $this->info('===========================================');
        $this->info('✅ Proceso completado!');
        $this->info('===========================================');
        $this->info("✅ Cuentas creadas: {$created}");
        $this->info("⏭️  Cuentas omitidas (ya existían): {$skipped}");

        if ($errors > 0) {
            $this->error("❌ Errores: {$errors}");
        }

        $this->newLine();
        $this->info('📧 Credenciales por defecto:');
        $this->info("   Email: [email del usuario en Digitra]");
        $this->info("   Password: {$defaultPassword}");
        $this->newLine();

        $this->info('💡 Los usuarios pueden cambiar su password después del primer login.');
        $this->newLine();

        // Mostrar algunos ejemplos
        if ($created > 0) {
            $this->info('📋 Ejemplos de cuentas creadas:');
            $sampleUsers = User::whereNotNull('tenant_id')
                ->where('is_super_admin', false)
                ->with('tenant')
                ->take(5)
                ->get();

            foreach ($sampleUsers as $user) {
                $this->line("   • {$user->email} - {$user->tenant->name}");
            }

            if ($created > 5) {
                $this->line("   ... y " . ($created - 5) . " más");
            }
        }

        $this->newLine();
        $this->info('🌐 Los usuarios pueden acceder en: http://127.0.0.1:8003/admin');

        return Command::SUCCESS;
    }
}
