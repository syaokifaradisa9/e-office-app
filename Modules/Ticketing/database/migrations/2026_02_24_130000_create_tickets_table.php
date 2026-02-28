<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Ticketing\Enums\TicketStatus;
use Modules\Ticketing\Enums\TicketPriority;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('asset_item_id')->constrained('asset_items')->onDelete('cascade');
            $table->enum('status', TicketStatus::values())->default(TicketStatus::PENDING->value);
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', TicketPriority::values())->nullable();
            $table->enum('real_priority', TicketPriority::values())->nullable();
            $table->json('attachments')->nullable();
            $table->text('follow_up')->nullable();
            $table->text('note')->nullable();
            $table->json('process_attachments')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->text('feedback_description')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
