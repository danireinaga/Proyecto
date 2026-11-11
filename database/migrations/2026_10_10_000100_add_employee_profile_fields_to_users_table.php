<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombres', 100)->nullable();
            $table->string('apellido_paterno', 100)->nullable();
            $table->string('apellido_materno', 100)->nullable();
            $table->string('celular', 25)->nullable();
            $table->string('carnet_identidad', 30)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['carnet_identidad']);
            $table->dropColumn([
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'celular',
                'carnet_identidad',
            ]);
        });
    }
};
