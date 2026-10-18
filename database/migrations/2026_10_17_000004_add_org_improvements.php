<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->unsignedBigInteger('head_id')->nullable()->after('description');
            $table->index('head_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('email');
            $table->softDeletes();
        });

        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_periods', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'deleted_at']);
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex(['head_id']);
            $table->dropColumn('head_id');
        });
    }
};
