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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('status')->default('completed');
            $table->foreignId('koi_id')->constrained('kois')->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('sell_jpy', 12, 2);
            $table->decimal('sell_idr', 12, 2);
            $table->decimal('margin', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0);
            $table->decimal('stock_jpy', 12, 2);
            $table->decimal('stock_idr', 12, 2);
            $table->string('sell_to');
            $table->decimal('profit', 12, 2)->default(0);
            $table->timestamps();
            
            $table->index('date');
            $table->index('koi_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
