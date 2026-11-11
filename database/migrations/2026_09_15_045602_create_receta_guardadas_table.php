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
        Schema::create('recetas_guardadas', function (Blueprint $table) {
            $table->id();
            $table->string('meal_id')->unique(); // ID único que viene de la API de TheMealDB
            $table->string('nombre');          // Nombre del postre
            $table->string('imagen')->nullable(); // URL de la foto del postre
            $table->text('instrucciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recetas_guardadas');
    }
};
