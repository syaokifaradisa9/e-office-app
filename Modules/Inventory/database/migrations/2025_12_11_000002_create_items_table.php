<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('category_items')->nullOnDelete();
            $table->string('image_url')->nullable();
            $table->string('name');
            $table->string('unit_of_measure')->default('pcs');
            $table->integer('stock')->default(0);
            $table->text('description')->nullable();
            $table->foreignId('reference_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('main_reference_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->integer('multiplier')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
