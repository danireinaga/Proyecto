<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->string('tipo_encargo', 120)->nullable();
            $table->string('ocasion', 80)->nullable();
            $table->unsignedSmallInteger('porciones')->nullable();
            $table->string('sabor', 120)->nullable();
            $table->string('relleno', 120)->nullable();
            $table->string('cobertura', 120)->nullable();
            $table->string('colores', 255)->nullable();
            $table->text('decoracion')->nullable();
            $table->string('texto_torta', 255)->nullable();
            $table->text('alergias')->nullable();
            $table->string('imagen_referencia')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropColumn([
                'tipo_encargo',
                'ocasion',
                'porciones',
                'sabor',
                'relleno',
                'cobertura',
                'colores',
                'decoracion',
                'texto_torta',
                'alergias',
                'imagen_referencia',
            ]);
        });
    }
};
