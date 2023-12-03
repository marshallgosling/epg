<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatistic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statistic', function (Blueprint $table) {
            $table->id();
            $table->string('model', 40)->comment('模型名称');
            $table->string('column', 20)->comment('统计属性');
            $table->unsignedInteger('value')->comment('统计值')->default(0);
            $table->unsignedTinyInteger('type')->comment('类型：每日/累计')->default(0);
            $table->string('group', 20)->comment('分组');
            $table->string('category', 10)->comment('栏目')->nullable();
            $table->string('date', 20)->comment('统计日期')->nullable();
            $table->string('comment',200)->comment('备注')->nullable();
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
        Schema::dropIfExists('statistic');
    }
}
