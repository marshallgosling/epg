<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40)->comment('标题');
            $table->string('message', 255)->comment('通知内容');
            $table->unsignedTinyInteger('type')->default(0)->comment('通知类型');
            $table->string('level', 20)->comment('通知级别')->nullable();
            $table->string('user', 50)->comment('用户')->nullable();
            $table->unsignedTinyInteger('viewed')->default(0)->comment('是否已读');
            $table->string('group_id', 10)->comment('频道分组');
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
        Schema::dropIfExists('notification');
    }
}
