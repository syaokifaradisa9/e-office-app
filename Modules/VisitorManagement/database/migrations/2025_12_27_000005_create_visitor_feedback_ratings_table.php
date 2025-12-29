<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visitor_feedback_ratings', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('visitor_feedback_id')->constrained('visitor_feedbacks')->onDelete('cascade');
            $blueprint->foreignId('question_id')->constrained('visitor_feedback_questions')->onDelete('cascade');
            $blueprint->tinyInteger('rating')->unsigned(); // 1-5
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_feedback_ratings');
    }
};
