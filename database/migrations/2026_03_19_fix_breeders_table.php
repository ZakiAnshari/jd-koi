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
        // Drop the existing breeders table
        Schema::dropIfExists('breeders');
        
        // Recreate the breeders table with only name_breeder
        Schema::create('breeders', function (Blueprint $table) {
            $table->id();
            $table->string('name_breeder');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breeders');
    }
};
