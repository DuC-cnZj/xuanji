<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_configs', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id')->comment('gitlab project ID');
            $table->string('config_file')->default('')->comment('默认配置文件');
            $table->string('config_file_type')->default('.env')->comment('.env/yaml/php');
            $table->string('chart_version')->default('')->comment('chart_version');
            $table->string('repository')->comment('镜像仓库');
            $table->string('local_chart')->comment('本地 chart, .tgz 类型');
            $table->string('chart')->comment('chart');
            $table->string('helm_repo_name')->default('')->comment('helm repo name');
            $table->string('helm_repo_url')->default('')->comment('helm repo url');
            $table->json('default_values')->nullable()->comment('仓库默认的 helm values');
            $table->string('tag_format')->comment('tag 格式 默认变量 $branch $commit');
            $table->string('config_field')->default('')->comment('values.yaml config 字段');
            $table->json('branches')->nullable()->comment('启用xuanji的分支');
            $table->boolean('is_simple_env')->default(true)->comment('是否是单个env cm，既单个变量不可配');
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
        Schema::dropIfExists('project_configs');
    }
}
