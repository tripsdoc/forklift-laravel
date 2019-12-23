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
            $table->bigIncrements('id');
            $table->bigInteger('ShifterId');
            $table->bigInteger('parkId')->nullable();
            $table->bigInteger('CntrId')->nullable();
            $table->timestamp('requestIn');
            $table->string('status');
            $table->timestamp('created_at')->useCurrent();
            $table->string('created_by');
            $table->timestamp('updated_at')->nullable();
            $table->string('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temporary_park');
    }
}
