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
        Schema::create('archieve_document_category', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('document_id')->constrained('archieve_documents')->onDelete('cascade');
            $blueprint->foreignId('category_id')->constrained('archieve_categories')->onDelete('cascade');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archieve_document_category');
    }
};
