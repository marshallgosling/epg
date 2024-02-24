<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Exportjobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('export_job', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('')->comment('名称');
            $table->string('start_at', 20)->comment("开始日期")->nullable();
            $table->string('end_at', 20)->comment('结束日期')->nullable();
            $table->string('filename', 50)->default('')->comment('文件名');
            $table->unsignedTinyInteger('type')->default(0)->comment('类型');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->string('group_id', 10)->default('')->comment("频道组")->nullable();
            $table->string('reason', 500)->comment('错误原因')->nullable();
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
        Schema::dropIfExists('export_job');
    }
}
