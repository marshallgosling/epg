<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpiration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expiration', function (Blueprint $table) {
            $table->id();
            $table->string('group_id', 10)->comment("频道");
            $table->string('name', 100)->comment("节目名");
            $table->date('start_at')->comment("具体播出起始日期");
            $table->date('end_at')->comment("具体播出结束日期");
            $table->unsignedTinyInteger('status')->comment('状态')->default(0);
            $table->string('comment', 255)->comment("备注")->nullable();
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
        Schema::dropIfExists('expiration');
    }
}
