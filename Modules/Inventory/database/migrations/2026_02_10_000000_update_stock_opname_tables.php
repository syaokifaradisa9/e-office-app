<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->timestamp('confirmed_at')->nullable()->after('status');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->integer('final_stock')->nullable()->after('difference');
            $table->text('final_notes')->nullable()->after('final_stock');
        });
    }

    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropColumn('confirmed_at');
        });

        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropColumn(['final_stock', 'final_notes']);
        });
    }
};
