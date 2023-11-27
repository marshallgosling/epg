<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBlacklist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 50)->comment('关键字');
            $table->string('group', 20)->comment('分组')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->timestamp('scaned_at')->comment('上次扫描时间')->nullable();
            $table->longText('data')->comment('扫描数据')->nullable();
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
        Schema::dropIfExists('blacklist');
    }
}
