<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items') && ! Schema::hasTable('venta_items')) {
            Schema::rename('order_items', 'venta_items');
        } elseif (Schema::hasTable('order_items') && Schema::hasTable('venta_items')) {
            throw new RuntimeException('Both order_items and venta_items tables exist; refusing to risk sales data.');
        }

        if (Schema::hasTable('orders') && ! Schema::hasTable('ventas')) {
            Schema::rename('orders', 'ventas');
        } elseif (Schema::hasTable('orders') && Schema::hasTable('ventas')) {
            throw new RuntimeException('Both orders and ventas tables exist; refusing to risk sales data.');
        }

        Schema::dropIfExists('recetas_guardadas');
    }

    public function down(): void
    {
        if (Schema::hasTable('ventas') && ! Schema::hasTable('orders')) {
            Schema::rename('ventas', 'orders');
        }

        if (Schema::hasTable('venta_items') && ! Schema::hasTable('order_items')) {
            Schema::rename('venta_items', 'order_items');
        }

        if (! Schema::hasTable('recetas_guardadas')) {
            Schema::create('recetas_guardadas', function (Blueprint $table): void {
                $table->id();
                $table->string('meal_id')->unique();
                $table->string('nombre');
                $table->string('imagen')->nullable();
                $table->text('instrucciones')->nullable();
                $table->timestamps();
            });
        }
    }
};
