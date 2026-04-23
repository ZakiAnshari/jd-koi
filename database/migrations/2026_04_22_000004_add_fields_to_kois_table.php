<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->foreignId('jenis_koi_id')->nullable()->after('breeder_id')->constrained('jenis_kois')->nullOnDelete();
            $table->unsignedSmallInteger('age')->nullable()->after('name')->comment('bulan');
            $table->decimal('size', 8, 2)->nullable()->after('age')->comment('cm');
            $table->unsignedInteger('box_qty')->nullable()->after('size');
            $table->decimal('weight', 8, 3)->nullable()->after('box_qty')->comment('kg');
            $table->decimal('total_weight', 10, 3)->nullable()->after('weight')->comment('kg');
        });
    }

    public function down(): void
    {
        Schema::table('kois', function (Blueprint $table) {
            $table->dropForeign(['jenis_koi_id']);
            $table->dropColumn(['jenis_koi_id', 'age', 'size', 'box_qty', 'weight', 'total_weight']);
        });
    }
};
