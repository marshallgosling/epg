<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKeywords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 200)->comment('关键字');
            $table->string('group', 20)->comment('分组')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->string('value', 200)->comment('值')->nullable();
            $table->string('language', 20)->comment('语言')->nullable();
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
        Schema::dropIfExists('keywords');
    }
}
