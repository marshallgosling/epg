<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 模版记录表
        Schema::create('template', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('')->comment('模版编号名称');
            $table->unsignedSmallInteger("schedule")->default(0)->comment('普通模版为0，特殊模版对应所在星期的数字');
            $table->string('start_at', 6)->default('')->comment("开始时间");
            $table->string('end_at', 6)->default('')->comment('结束时间');
            $table->string('duration', 50)->default('')->comment('预估时长');
            $table->unsignedInteger('version')->default(0)->comment('版本号');
            $table->string('group_id', 10)->default('')->comment("分组号")->nullable();
            $table->string('comment', 255)->default('')->comment('模版说明')->nullable();
            $table->timestamps();
        });

        // 模版节目表
        Schema::create('template_programs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('')->comment('名称');
            $table->string("category", 20)->default('')->comment('栏目分类');
            $table->string('type', 20)->default('')->comment("类型");
            $table->unsignedInteger('template_id')->default(0);
            $table->unsignedInteger('order_no')->default(0)->comment('排序号');
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
        Schema::dropIfExists('template');
        Schema::dropIfExists('template_programs');
    }
}
