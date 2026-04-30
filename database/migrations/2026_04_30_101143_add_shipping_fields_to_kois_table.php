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
            $table->decimal('shipping_cost', 15, 2)->nullable()->after('azukari_period');
            $table->integer('shipping_qty')->nullable()->after('shipping_cost');
            $table->string('shipping_note')->nullable()->after('shipping_qty');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropColumn(['shipping_cost', 'shipping_qty', 'shipping_note']);
        });
    }
};
