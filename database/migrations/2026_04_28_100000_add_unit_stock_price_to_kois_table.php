<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->nullable()->after('sell_idr');
            $table->decimal('stock_price', 15, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropColumn(['unit_price', 'stock_price']);
        });
    }
};
