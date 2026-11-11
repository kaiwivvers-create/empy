<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A login can only ever own one employee record. Detach duplicates
        // before the unique index is created so the migration cannot fail.
        $duplicates = DB::table('employees')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicates as $userId) {
            $keep = DB::table('employees')
                ->where('user_id', $userId)
                ->orderBy('id')
                ->value('id');

            DB::table('employees')
                ->where('user_id', $userId)
                ->where('id', '!=', $keep)
                ->update(['user_id' => null]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
