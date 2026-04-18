<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('katalog_kois', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('koi_id')->constrained('kois')->cascadeOnDelete();
            $table->foreignId('pembelian_id')->unique()->constrained('purchases')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->decimal('sell_jpy', 15, 2)->default(0);
            $table->decimal('kurs', 15, 2)->default(0);
            $table->decimal('sell_idr', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('stock_jpy', 15, 2)->default(0);
            $table->decimal('stock_idr', 15, 2)->default(0);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('katalog_kois');
    }
};
