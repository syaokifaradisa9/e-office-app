<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archieve_document_division', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('archieve_documents')->onDelete('cascade');
            $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
            $table->bigInteger('allocated_size')->default(0);
            $table->timestamps();

            $table->unique(['document_id', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archieve_document_division');
    }
};
