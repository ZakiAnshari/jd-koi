<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->string('sale_invoice')->nullable()->after('sale_status');
            $table->date('sale_shipped_date')->nullable()->after('sale_invoice');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropColumn(['sale_invoice', 'sale_shipped_date']);
        });
    }
};
