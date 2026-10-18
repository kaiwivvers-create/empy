<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->decimal('manager_score', 5, 2)->nullable()->after('final_score');
        });

        Schema::create('peer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('relationship', 20)->default('peer');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['evaluation_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_feedbacks');

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('manager_score');
        });
    }
};
