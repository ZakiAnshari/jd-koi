<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('sell_jpy', 15, 2)->default(0)->change();
            $table->decimal('sell_idr', 15, 2)->default(0)->change();
            $table->decimal('margin', 15, 2)->default(0)->change();
            $table->decimal('fee', 15, 2)->default(0)->change();
            $table->decimal('profit', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('sell_jpy', 15, 2)->default(null)->change();
            $table->decimal('sell_idr', 15, 2)->default(null)->change();
            $table->decimal('margin', 15, 2)->default(null)->change();
            $table->decimal('fee', 15, 2)->default(null)->change();
            $table->decimal('profit', 15, 2)->default(null)->change();
        });
    }
};
