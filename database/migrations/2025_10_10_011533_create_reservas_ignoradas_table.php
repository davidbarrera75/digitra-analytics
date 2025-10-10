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
        Schema::create('reservas_ignoradas', function (Blueprint $table) {
            $table->id();

            // ID de la reserva en Digitra (MySQL)
            $table->unsignedBigInteger('reserva_id')->unique();

            // Motivo por el cual se ignora (duplicado, error, etc.)
            $table->enum('motivo', ['duplicada', 'error_datos', 'test', 'otro'])->default('duplicada');

            // Notas adicionales
            $table->text('notas')->nullable();

            // Usuario que la marcó como ignorada
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
        Schema::dropIfExists('reservas_ignoradas');
    }
};
