<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Datos del cliente (el campo nombre es el que nunca se guardaba)
            $table->string('cliente_nombre')->nullable()->after('user_id');
            $table->string('cliente_apellido')->nullable()->after('cliente_nombre');
            $table->string('cliente_telefono', 40)->nullable()->after('cliente_apellido');
            $table->string('cliente_nit', 40)->nullable()->after('cliente_telefono');
            $table->string('cliente_direccion')->nullable()->after('cliente_nit');

            // Cobro
            $table->string('pago_tipo', 20)->default('efectivo')->after('cliente_direccion');
            $table->decimal('pago_recibido', 10, 2)->default(0)->after('pago_tipo');
            $table->decimal('pago_vuelto', 10, 2)->default(0)->after('pago_recibido');
            $table->decimal('descuento', 10, 2)->default(0)->after('pago_vuelto');
            $table->decimal('propina', 10, 2)->default(0)->after('descuento');

            // Total final = subtotal - descuento + propina
            $table->decimal('total_final', 10, 2)->default(0)->after('total');

            // Trazabilidad
            $table->string('origen', 20)->default('caja')->after('estado');
            $table->string('numero_ficha')->nullable()->after('origen');
            $table->text('notas')->nullable()->after('numero_ficha');

            $table->index('cliente_nombre');
            $table->index('numero_ficha');
            $table->index('origen');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['cliente_nombre']);
            $table->dropIndex(['numero_ficha']);
            $table->dropIndex(['origen']);

            $table->dropColumn([
                'cliente_nombre', 'cliente_apellido', 'cliente_telefono',
                'cliente_nit', 'cliente_direccion', 'pago_tipo',
                'pago_recibido', 'pago_vuelto', 'descuento', 'propina',
                'total_final', 'origen', 'numero_ficha', 'notas',
            ]);
        });
    }
};
