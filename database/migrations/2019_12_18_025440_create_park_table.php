<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HSC_Park', function (Blueprint $table) {
            $table->bigIncrements('ParkID');
            $table->string('Name');
            $table->integer('Type');// 1 : Warehouse, 2 : Parking lots, 3 : Temporary
            $table->string('Place'); 
            $table->text('Detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('HSC_Park');
    }
}
