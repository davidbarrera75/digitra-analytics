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
        // SQLite no permite eliminar foreign keys directamente
        // Necesitamos recrear la tabla sin la constraint

        Schema::dropIfExists('gastos_mensuales');

        Schema::create('gastos_mensuales', function (Blueprint $table) {
            $table->id();

            // Solo el ID del establecimiento, sin foreign key (cross-database)
            $table->unsignedBigInteger('establecimiento_id');

            $table->unsignedTinyInteger('mes')->comment('Mes (1-12)');
            $table->unsignedSmallInteger('año');
            $table->decimal('aseo', 15, 2)->default(0)->comment('Valor pagado en aseo');
            $table->decimal('administracion', 15, 2)->default(0)->comment('Valor administración edificio');
            $table->decimal('otros_gastos', 15, 2)->default(0)->comment('Otros gastos');
            $table->text('notas')->nullable()->comment('Notas adicionales');
            $table->timestamps();

            // Unique constraint para prevenir duplicados
            $table->unique(['establecimiento_id', 'mes', 'año'], 'unique_gasto_mensual');

            // Índice para mejorar performance en queries
            $table->index(['establecimiento_id', 'año', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos_mensuales');

        // Recrear con la foreign key
        Schema::create('gastos_mensuales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establecimiento_id')->constrained('establecimientos')->onDelete('cascade');
            $table->unsignedTinyInteger('mes')->comment('Mes (1-12)');
            $table->unsignedSmallInteger('año');
            $table->decimal('aseo', 15, 2)->default(0)->comment('Valor pagado en aseo');
            $table->decimal('administracion', 15, 2)->default(0)->comment('Valor administración edificio');
            $table->decimal('otros_gastos', 15, 2)->default(0)->comment('Otros gastos');
            $table->text('notas')->nullable()->comment('Notas adicionales');
            $table->timestamps();

            $table->unique(['establecimiento_id', 'mes', 'año'], 'unique_gasto_mensual');
        });
    }
};
