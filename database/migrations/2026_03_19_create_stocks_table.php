<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koi_id')->constrained('kois')->cascadeOnDelete();
            $table->date('date');
            $table->integer('qty');
            $table->decimal('price_jpy', 12, 2);
            $table->decimal('price_idr', 12, 2);
            $table->decimal('total_jpy', 12, 2);
            $table->decimal('total_idr', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
