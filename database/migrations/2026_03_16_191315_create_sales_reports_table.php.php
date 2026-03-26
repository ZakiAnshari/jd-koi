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
        Schema::create('sales_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->enum('status', ['sold', 'pending', 'cancelled', 'reserved'])->default('pending');
            $table->string('breeder')->nullable();
            $table->string('code')->nullable();
            $table->string('photo')->nullable();
            $table->string('name');
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('sell_jpy', 15, 2)->default(0);
            $table->decimal('sell_idr', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('stock_jpy', 15, 2)->default(0);
            $table->decimal('stock_idr', 15, 2)->default(0);
            $table->string('sell_to')->nullable();
            $table->decimal('profit', 15, 2)->default(0);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index(['year', 'month']);
            $table->index('status');
            $table->index('breeder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
