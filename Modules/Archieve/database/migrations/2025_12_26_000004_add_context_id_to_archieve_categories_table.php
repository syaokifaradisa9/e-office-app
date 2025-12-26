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
        Schema::table('archieve_categories', function (Blueprint $table) {
            $table->foreignId('context_id')->after('name')->nullable()->constrained('archieve_category_contexts')->onDelete('cascade');
            $table->string('type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archieve_categories', function (Blueprint $table) {
            $table->dropForeign(['context_id']);
            $table->dropColumn('context_id');
            $table->string('type')->nullable(false)->change();
        });
    }
};
