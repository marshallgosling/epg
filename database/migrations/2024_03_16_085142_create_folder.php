<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFolder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('folder', function (Blueprint $table) {
            $table->id();
            $table->string('path', 100)->comment('文件夹路径');
            $table->text('data')->nullable()->comment('扫描结果');
            $table->string('comment', 200)->nullable()->comment('备注');
            $table->dateTime('scaned_at')->nullable()->comment('上次扫描时间');
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
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
        Schema::dropIfExists('folder');
    }
}
