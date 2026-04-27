<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('jenis_koi_id')->constrained('users')->nullOnDelete();
            $table->date('sale_tanggal')->nullable()->after('customer_id');
            $table->integer('sale_qty')->nullable()->after('sale_tanggal');
            $table->decimal('sell_jpy', 15, 2)->nullable()->after('sale_qty');
            $table->decimal('kurs', 15, 2)->nullable()->after('sell_jpy');
            $table->decimal('sell_idr', 15, 2)->nullable()->after('kurs');
            $table->decimal('fee', 15, 2)->nullable()->default(0)->after('sell_idr');
            $table->string('sale_status')->nullable()->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'sale_tanggal', 'sale_qty', 'sell_jpy', 'kurs', 'sell_idr', 'fee', 'sale_status']);

        });
    }
};
