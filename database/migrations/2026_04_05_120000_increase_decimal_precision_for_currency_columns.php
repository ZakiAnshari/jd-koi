<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('price_jpy', 20, 2)->change();
            $table->decimal('price_idr', 20, 2)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('sell_jpy', 20, 2)->change();
            $table->decimal('sell_idr', 20, 2)->change();
            $table->decimal('fee', 20, 2)->default(0)->change();
            $table->decimal('margin', 20, 2)->default(0)->change();
            $table->decimal('profit', 20, 2)->default(0)->change();
            $table->decimal('stock_jpy', 20, 2)->default(0)->change();
            $table->decimal('stock_idr', 20, 2)->default(0)->change();
        });

        Schema::table('sales_reports', function (Blueprint $table) {
            $table->decimal('sell_jpy', 20, 2)->default(0)->change();
            $table->decimal('sell_idr', 20, 2)->default(0)->change();
            $table->decimal('margin', 20, 2)->default(0)->change();
            $table->decimal('fee', 20, 2)->default(0)->change();
            $table->decimal('stock_jpy', 20, 2)->default(0)->change();
            $table->decimal('stock_idr', 20, 2)->default(0)->change();
            $table->decimal('profit', 20, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('price_jpy', 12, 2)->change();
            $table->decimal('price_idr', 12, 2)->change();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('sell_jpy', 12, 2)->change();
            $table->decimal('sell_idr', 12, 2)->change();
            $table->decimal('fee', 12, 2)->default(0)->change();
            $table->decimal('margin', 12, 2)->default(0)->change();
            $table->decimal('profit', 12, 2)->default(0)->change();
            $table->decimal('stock_jpy', 15, 2)->default(0)->change();
            $table->decimal('stock_idr', 15, 2)->default(0)->change();
        });

        Schema::table('sales_reports', function (Blueprint $table) {
            $table->decimal('sell_jpy', 15, 2)->default(0)->change();
            $table->decimal('sell_idr', 15, 2)->default(0)->change();
            $table->decimal('margin', 15, 2)->default(0)->change();
            $table->decimal('fee', 15, 2)->default(0)->change();
            $table->decimal('stock_jpy', 15, 2)->default(0)->change();
            $table->decimal('stock_idr', 15, 2)->default(0)->change();
            $table->decimal('profit', 15, 2)->default(0)->change();
        });
    }
};
