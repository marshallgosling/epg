<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEpg extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('epg', function (Blueprint $table) {
            $table->id();
            $table->string('group_id')->default('')->comment("频道组");
            $table->string('name', 50)->default('')->comment('节目名称');
            $table->unsignedInteger('channel_id')->default(0)->comment("频道ID")->index('channel');
            $table->timestamp('start_at')->comment("开始时间")->nullable();
            $table->timestamp('end_at')->comment('结束时间')->nullable();
            $table->string('duration', 50)->default('')->comment('时长');
            $table->string('unique_no', 50)->default('')->comment('播出编号');
            $table->string('category', 50)->default('')->comment('栏目');
            $table->unsignedInteger('program_id')->default(0)->comment("节目栏目组ID");
            $table->string('comment')->nullable();
            //$table->timestamps();
            $table->index(['start_at', 'end_at'], 'start_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('epg');
    }
}
