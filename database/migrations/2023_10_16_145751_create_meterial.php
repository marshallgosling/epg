<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeterial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('meterial', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('');
            $table->string('unique_no', 20)->default('')->comment('播出编号');
            $table->string('category', 6)->default('')->comment('分类');
            $table->string('duration', 20)->default('')->comment('时长');
            $table->unsignedInteger('size')->default(0)->comment('文件长度');
            $table->unsignedInteger('frames')->default(0)->comment('帧数');
            $table->string('comment',200)->nullable()->comment('注释');
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
        Schema::dropIfExists('meterial');
    }
}
