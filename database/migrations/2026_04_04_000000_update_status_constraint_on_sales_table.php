<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_status_check");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_status_check CHECK (status IN ('azukari', 'cancel', 'cancel_dead', 'mudpond_hiroshima', 'na', 'oyagoi', 'pending', 'shipped', 'sold'))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_status_check");
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_status_check CHECK (status IN ('completed', 'pending', 'cancelled'))");
    }
};
