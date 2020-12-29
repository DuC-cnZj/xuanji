<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('project_id')->comment('gitlab project id');
            $table->string('branch')->comment('gitlab branch');
            $table->string('commit')->comment('gitlab commit');
            $table->text('env')->nullable()->comment('配置文件');
            $table->json('config_snapshot')->nullable()->comment('配置快照');
            $table->string('creator')->comment('创建人');

            $table->foreignId('ns_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('projects');
    }
}
