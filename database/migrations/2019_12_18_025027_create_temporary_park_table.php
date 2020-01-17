<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemporaryParkTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('HSC_OngoingPark', function (Blueprint $table) {
            $table->bigIncrements('ParkingID');
            $table->bigInteger('ParkingLot')->nullable();
            $table->bigInteger('Dummy')->nullable();
            $table->string('createdBy')->nullable();
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
        Schema::dropIfExists('HSC_OngoingPark');
    }
}
