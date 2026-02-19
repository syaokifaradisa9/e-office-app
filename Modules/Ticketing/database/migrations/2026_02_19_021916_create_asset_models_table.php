<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Ticketing\Enums\AssetModelType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_models', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->enum('type', AssetModelType::values());
            $blueprint->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $blueprint->integer('maintenance_count')->default(0);
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_models');
    }
};
