<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgram extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->default('');
            $table->string('unique_no', 20)->default('')->comment('编号');
            $table->string('category', 128)->default('')->comment('类别');
            $table->string('album', 200)->default('')->comment('专辑')->nullable();
            $table->string('artist', 128)->default('')->comment('表演者')->nullable();
            $table->string('co_artist', 128)->default('')->comment('联合创作')->nullable();
            $table->string('gender', 50)->default('')->comment('性别')->nullable();
            $table->string('mood', 20)->default('')->comment('情绪')->nullable();
            $table->string('energy', 20)->default('')->comment('力量')->nullable();
            $table->string('tempo', 20)->default('')->comment('节奏')->nullable();
            $table->string('lang', 20)->default('')->comment('语言')->nullable();
            $table->string('black', 20)->comment('黑名单')->nullable();
            $table->string('duration', 20)->default('')->comment('时长')->nullable();
            $table->string('genre', 50)->default('')->comment('曲风')->nullable();
            $table->string('author', 50)->default('')->comment('作曲')->nullable();
            $table->string('lyrics', 50)->default('')->comment('作词')->nullable();
            $table->string('company', 50)->default('')->comment('唱片公司')->nullable();
            $table->string('air_date', 20)->default('')->comment('首播日期')->nullable();
            $table->string('product_date', 20)->default('')->comment('制作完成日期')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->string('comment', 128)->default('')->comment('备注')->nullable();
            $table->string('uuid', 50)->nullable();
            
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
        Schema::dropIfExists('program');
    }
}
