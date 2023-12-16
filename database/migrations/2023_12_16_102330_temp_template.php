<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TempTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 模版记录表
        Schema::create('temp_template', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('name', 50)->default('')->comment('模版编号名称');
            $table->unsignedSmallInteger("schedule")->default(0)->comment('普通模版为0，计划模版则对应所在星期的数字');
            $table->string('start_at', 22)->default('')->comment("开始时间");
            $table->unsignedTinyInteger('sort')->default(0)->comment('排序');
            $table->string('duration', 50)->default('')->comment('预估时长');
            $table->unsignedInteger('version')->default(0)->comment('版本号');
            $table->string('group_id', 10)->default('')->comment("分组号")->nullable();
            $table->string('comment', 255)->default('')->comment('模版说明')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->comment('模版状态，使用中，未启用，停用');
            $table->timestamps();
        });

        // 模版节目表
        Schema::create('temp_template_programs', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->string('name', 50)->default('')->comment('名称');
            $table->string("category", 20)->default('')->comment('栏目分类');
            $table->text('data')->comment("数据")->nullable();
            $table->unsignedInteger('template_id')->default(0);
            $table->unsignedInteger('sort')->default(0)->comment('排序号');
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
        Schema::dropIfExists('temp_template');
        Schema::dropIfExists('temp_template_programs');
    }
}
