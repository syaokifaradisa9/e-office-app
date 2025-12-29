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
        Schema::create('visitor_feedbacks', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('visitor_id')->constrained('visitors')->onDelete('cascade');
            $blueprint->text('feedback_note')->nullable();
            $blueprint->boolean('is_read')->default(false);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_feedbacks');
    }
};
