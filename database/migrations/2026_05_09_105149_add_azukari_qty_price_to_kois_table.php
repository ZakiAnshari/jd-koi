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
        Schema::table('kois', function (Blueprint $table) {
            $table->unsignedInteger('azukari_qty')->nullable()->after('azukari_period');
            $table->decimal('azukari_price_jpy', 15, 2)->nullable()->after('azukari_qty');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropColumn(['azukari_qty', 'azukari_price_jpy']);
        });
    }
};
