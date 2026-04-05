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
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('stock_jpy', 15, 2)->default(0)->after('sell_idr');
            $table->decimal('stock_idr', 15, 2)->default(0)->after('stock_jpy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['stock_jpy', 'stock_idr']);
        });
    }
};
