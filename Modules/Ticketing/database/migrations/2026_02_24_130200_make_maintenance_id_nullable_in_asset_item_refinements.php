<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_item_refinements', function (Blueprint $table) {
            // Make maintenance_id nullable so refinements can belong to tickets too
            $table->unsignedBigInteger('maintenance_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('asset_item_refinements', function (Blueprint $table) {
            $table->unsignedBigInteger('maintenance_id')->nullable(false)->change();
        });
    }
};
