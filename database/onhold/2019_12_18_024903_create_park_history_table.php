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
            $table->bigIncrements('id');
            $table->string('Driver');
            $table->string('Park');
            $table->bigInteger('CntrId');
            $table->dateTime('requestIn');
            $table->dateTime('finishTime')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->string('created_by');
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
