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
        Schema::create('archieve_division_storages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->unique()->constrained('divisions')->onDelete('cascade');
            $table->bigInteger('max_size')->default(0); // in bytes
            $table->bigInteger('used_size')->default(0); // in bytes
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archieve_division_storages');
    }
};
