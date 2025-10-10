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
        Schema::create('reservas_corregidas', function (Blueprint $table) {
            $table->id();

            // ID de la reserva en Digitra (MySQL)
            $table->unsignedBigInteger('reserva_id')->unique();

            // Precio original (para referencia)
            $table->decimal('precio_original', 15, 2);

            // Precio corregido
            $table->decimal('precio_corregido', 15, 2);

            // Motivo de la corrección
            $table->enum('motivo', ['valor_atipico', 'error_digitacion', 'ajuste_manual', 'otro'])->default('valor_atipico');

            // Notas adicionales
            $table->text('notas')->nullable();

            // Usuario que realizó la corrección
            $table->unsignedBigInteger('user_id')->nullable();

            $table->timestamps();

            // Índice para búsquedas rápidas
            $table->index('reserva_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas_corregidas');
    }
};
