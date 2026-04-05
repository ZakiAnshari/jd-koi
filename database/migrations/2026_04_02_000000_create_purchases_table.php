<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('koi_id')->constrained()->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('price_jpy', 12, 2);
            $table->decimal('price_idr', 12, 2);
            $table->enum('status', ['completed', 'cancelled'])->default('completed');
            $table->timestamps();
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
