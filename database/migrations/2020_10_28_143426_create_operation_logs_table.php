<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->string("user")->nullable();
            $table->ipAddress("ip")->nullable();
            $table->string("path")->nullable();
            $table->string("time")->nullable();
            $table->string("method")->nullable();
            $table->text("request")->nullable();
            $table->text("response")->nullable();
            $table->integer("response_code")->nullable();
            $table->string("user_agent")->nullable();
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
        Schema::dropIfExists('operation_logs');
    }
}
