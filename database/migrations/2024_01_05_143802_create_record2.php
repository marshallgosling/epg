<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecord2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('record2', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->default('');
            $table->string('name2', 150)->default('');
            $table->string('unique_no', 20)->default('')->comment('编号');
            $table->string('category', 128)->default('')->comment('类别');
            $table->string('episodes', 50)->default('')->comment('剧集')->nullable();
            $table->unsignedMediumInteger('ep')->default(1)->comment('集数');
            $table->unsignedInteger('seconds')->default(0)->index('seconds');
            $table->string('duration', 20)->default('')->comment('时长')->nullable();
            $table->string('black', 20)->comment('黑名单')->nullable();
            
            $table->string('air_date', 20)->default('')->comment('版权首播日期')->nullable();
            $table->string('expired_date', 20)->default('')->comment('版权过期日期')->nullable();
            $table->string('comment', 128)->default('')->comment('备注')->nullable();
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
        Schema::dropIfExists('record2');
    }
}
