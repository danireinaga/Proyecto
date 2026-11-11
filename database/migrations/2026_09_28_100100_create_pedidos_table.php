<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            // Código legible, ej. PED-0001
            $table->string('codigo', 20)->unique();

            // Datos del cliente
            $table->string('cliente_nombre');
            $table->string('cliente_apellido')->nullable();
            $table->string('cliente_telefono', 40)->nullable();
            $table->string('cliente_nit', 40)->nullable();
            $table->string('cliente_direccion')->nullable();

            // El pedido en sí
            $table->text('detalles')->nullable();
            $table->date('fecha_pedido');
            $table->date('fecha_entrega');
            $table->time('hora_entrega')->nullable();

            // pendiente | en_produccion | listo | entregado | cancelado
            $table->string('estado', 20)->default('pendiente')->index();

            // Dinero
            $table->decimal('total_estimado', 10, 2)->default(0);
            $table->decimal('abono', 10, 2)->default(0);

            // Quién atiende
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Sincronización opcional con Google Calendar
            $table->string('google_event_id')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['fecha_entrega', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
