<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaterial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('material', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->default('');
            $table->string('name2', 100)->nullable();
            $table->unsignedSmallInteger('ep')->default(1);
            $table->string('unique_no', 20)->default('')->comment('播出编号');
            $table->string('category', 10)->default('')->comment('分类');
            $table->string('duration', 20)->default('')->comment('时长');
            $table->unsignedInteger('size')->default(0)->comment('文件长度');
            $table->unsignedInteger('frames')->default(0)->comment('帧数');
            $table->string('comment',200)->nullable()->comment('注释');
            //$table->string('uuid', 128)->nullable();
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
        Schema::dropIfExists('material');
    }
}
