<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannelProgram extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel_program', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('')->comment('节目名称');
            $table->string('schedule_start_at', 6)->default('')->comment("计划开始时间");
            $table->string('schedule_end_at', 6)->default('')->comment('计划结束时间');
            $table->timestamp('start_at')->comment("开始时间")->nullable();
            $table->timestamp('end_at')->comment('结束时间')->nullable();
            $table->string('duration', 50)->default('')->comment('时长');
            $table->unsignedInteger('version')->default(0)->comment('版本号');
            $table->unsignedInteger('channel_id')->default(0)->comment("频道号")->nullable();
            $table->json("data")->comment("编排数据")->nullable();
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
        Schema::dropIfExists('channel_program');
    }
}
