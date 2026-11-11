<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('guard_name', 'web')
            ->whereIn('name', ['cajero', 'supervisor'])
            ->delete();
    }

    public function down(): void
    {
        // Removed role assignments cannot be safely restored automatically.
    }
};
