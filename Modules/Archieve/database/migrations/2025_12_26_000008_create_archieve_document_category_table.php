<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archieve_document_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('archieve_documents')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('archieve_categories')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['document_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archieve_document_category');
    }
};
