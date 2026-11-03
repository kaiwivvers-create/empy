<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One person can only lead one department. Clear any extra historical
        // assignments (keeping the first department) before adding the index.
        $seen = [];

        foreach (DB::table('departments')->whereNotNull('head_id')->orderBy('id')->get() as $department) {
            if (isset($seen[$department->head_id])) {
                DB::table('departments')->where('id', $department->id)->update(['head_id' => null]);

                continue;
            }

            $seen[$department->head_id] = true;
        }

        Schema::table('departments', function (Blueprint $table) {
            $table->unique('head_id');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropUnique(['head_id']);
        });
    }
};
