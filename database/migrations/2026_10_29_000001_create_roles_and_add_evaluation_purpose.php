<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->unique();
            $table->string('label', 60)->nullable();
            $table->string('label_id', 60)->nullable();
            $table->string('base', 20)->default('employee');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        foreach ([
            ['super_admin', 'Super Admin', 'Super Admin', 'admin'],
            ['admin', 'Administrator', 'Administrator', 'admin'],
            ['manager', 'Manager', 'Manajer', 'manager'],
            ['employee', 'Employee', 'Karyawan', 'employee'],
        ] as [$name, $label, $labelId, $base]) {
            DB::table('roles')->insert([
                'name' => $name,
                'label' => $label,
                'label_id' => $labelId,
                'base' => $base,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('purpose', 40)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('purpose');
        });

        Schema::dropIfExists('roles');
    }
};
