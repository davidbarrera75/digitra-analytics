<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del tenant (puede ser nombre del usuario Digitra)
            $table->string('slug')->unique(); // Identificador único (para URLs, subdominios, etc)
            $table->unsignedBigInteger('digitra_user_id')->unique(); // FK a users de Digitra
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Configuraciones personalizadas
            $table->timestamp('trial_ends_at')->nullable(); // Para sistema de suscripción
            $table->timestamps();

            // Indexes
            $table->index('digitra_user_id');
            $table->index('is_active');
        });

        // Agregar tenant_id a la tabla users (admins locales)
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->boolean('is_super_admin')->default(false)->after('tenant_id');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'is_super_admin']);
        });

        Schema::dropIfExists('tenants');
    }
};
