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
        Schema::create('park_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('parkId');
            $table->bigInteger('CntrId');
            $table->dateTime('parkIn');
            $table->dateTime('parkOut')->nullable();
            //Status (0: Finished, 1: Cancelled)
            $table->integer('status');
            $table->text('note')->nullable();
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
