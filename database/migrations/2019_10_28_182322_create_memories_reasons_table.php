<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemoriesReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('memories_reasons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('reason_id')->unsigned();
            $table->foreign('reason_id')->references('id')->on('reasons')->onDelete('cascade');
            $table->bigInteger('memory_id')->unsigned();
            $table->foreign('memory_id')->references('id')->on('memories')->onDelete('cascade');
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
        Schema::dropIfExists('memories_reasons');
    }
}
