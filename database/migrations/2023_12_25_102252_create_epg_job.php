<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEpgJob extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('epg_job', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('任务名称');
            $table->string('group_id', 20)->comment('分组');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->string('file', 100)->nullable()->comment('数据备份文件');
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
        Schema::dropIfExists('epg_job');
    }
}
