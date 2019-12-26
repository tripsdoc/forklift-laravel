<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShifterUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ShifterUser', function (Blueprint $table) {
            $table->bigIncrements('ShifterID');
            $table->string('Name');
            $table->string('UserName')->unique();
            $table->string('Password');
            $table->string('Warehouse')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shifter_user');
    }
}
