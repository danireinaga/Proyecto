<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->string('tipo', 30)->default('personalizado')->index();
        });

        DB::table('pedidos')
            ->where('detalles', 'Pedido enviado desde el Menú Digital.')
            ->update(['tipo' => 'menu_digital']);
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table): void {
            $table->dropIndex(['tipo']);
            $table->dropColumn('tipo');
        });
    }
};
