<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLargeFile extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('large_file', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment("文件名");
            $table->string('storage', 50)->comment('存储桶配置');
            $table->string('path', 200)->comment('文件完整路径');
            $table->string('target_path', 200)->comment('文件目标完整路径');
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
        Schema::dropIfExists('large_file');
    }
}
