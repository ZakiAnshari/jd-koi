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
            $table->date('shipping_date')->nullable()->after('shipping_note');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropColumn('shipping_date');
        });
    }
};
