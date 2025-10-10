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
        Schema::create('resumenes_mensuales_ia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('mes')->comment('Mes (1-12)');
            $table->unsignedSmallInteger('año');
            $table->text('contenido')->comment('Resumen generado por IA en formato markdown');
            $table->json('datos_estadisticos')->nullable()->comment('Datos usados para generar el resumen');
            $table->unsignedInteger('tokens_usados')->default(0)->comment('Tokens consumidos de Claude API');
            $table->timestamp('generado_en')->nullable();
            $table->timestamps();

            // Índices
            $table->unique(['user_id', 'mes', 'año']);
            $table->index(['user_id', 'año', 'mes']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resumenes_mensuales_ia');
    }
};
