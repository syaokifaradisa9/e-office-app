<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Modules\VisitorManagement\Enums\VisitorStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_name');
            $table->string('phone_number');
            $table->string('organization')->nullable();
            $table->string('photo_url')->nullable();
            
            $table->foreignId('division_id')->constrained('divisions')->onDelete('cascade');
            $table->foreignId('purpose_id')->constrained('visitor_purposes')->onDelete('cascade');
            
            $table->text('purpose_detail')->nullable();
            $table->integer('visitor_count')->default(1);
            
            $table->timestamp('check_in_at')->useCurrent();
            $table->timestamp('check_out_at')->nullable();
            
            $table->enum('status', VisitorStatus::values())->default(VisitorStatus::Pending->value);
            $table->text('admin_note')->nullable();
            
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('confirmed_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
