<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('roles')->insertOrIgnore([
            'name' => 'head_manager',
            'label' => 'Head Manager',
            'label_id' => 'Kepala Atasan',
            'base' => 'manager',
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'head_manager')->delete();
    }
};
