<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement('DROP TABLE IF EXISTS notifications CASCADE');
        \DB::statement('DROP TABLE IF EXISTS pesanans CASCADE');
        \DB::statement('DROP TABLE IF EXISTS katalog_koi_images CASCADE');
        \DB::statement('DROP TABLE IF EXISTS katalog_kois CASCADE');
    }

    public function down(): void
    {
        // not reversible
    }
};
