<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('koi_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->integer('qty');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['koi_id', 'reference_type', 'reference_id'], 'stock_movements_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
