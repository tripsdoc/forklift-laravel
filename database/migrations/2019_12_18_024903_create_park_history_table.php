<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParkHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HSC_ParkHistory', function (Blueprint $table) {
            $table->bigIncrements('HistoryID');
            $table->timestamp('SetDt');
            $table->timestamp('UnSetDt');
            $table->bigInteger('ParkingLot');
            $table->bigInteger('Dummy');
            $table->string('createdBy');
            $table->timestamp('createdDt')->useCurrent();
            $table->string('updatedBy')->nullable();
            $table->timestamp('updatedDt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('park_history');
    }
}
