<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan', function (Blueprint $table) {
            $table->id();
            $table->string('group_id')->comment("频道组");
            $table->string('name', 40)->comment('计划名称');
            $table->string('start_at', 20)->comment('开始时间');
            $table->string('end_at', 20)->comment('结束时间');
            $table->string('date_from', 20)->comment('起始日期');
            $table->string('date_to', 20)->comment('结束日期');
            $table->string('category', 20)->comment('栏目');
            $table->string('daysofweek', 20)->comment('每周播出日');
            $table->string('episodes', 50)->comment('节目名称');
            $table->unsignedTinyInteger('status')->comment('状态')->default(0);
            $table->unsignedTinyInteger('type')->comment('类型')->default(0);
            $table->text('data')->comment('模型数据')->nullable();
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
        Schema::dropIfExists('plan');
    }
}
