<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUniqueParkName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('HSC_Park', function(Blueprint $table)
        {
            $table->dropUnique('hsc_park_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('HSC_Park', function(Blueprint $table)
        {
            //Put the index back when the migration is rolled back
            $table->unique('name');

        });
    }
}
