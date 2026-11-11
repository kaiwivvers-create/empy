<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peer_feedback_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peer_feedback_id')->constrained()->cascadeOnDelete();
            $table->foreignId('criteria_id')->constrained('criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['peer_feedback_id', 'criteria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_feedback_scores');
    }
};
