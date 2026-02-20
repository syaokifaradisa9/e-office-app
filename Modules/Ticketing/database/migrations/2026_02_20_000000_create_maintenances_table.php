<?php

namespace Modules\Ticketing\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Ticketing\Enums\MaintenanceStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->date('estimation_date');
            $table->date('actual_date')->nullable();
            $table->text('note')->nullable();
            $table->json('checklist_results')->nullable();
            $table->enum('status', MaintenanceStatus::values())->default(MaintenanceStatus::PENDING->value);
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
