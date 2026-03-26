<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing breeders table with CASCADE to handle dependencies
        DB::statement('DROP TABLE IF EXISTS breeders CASCADE');
        
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
        DB::statement('DROP TABLE IF EXISTS breeders CASCADE');
    }
};
