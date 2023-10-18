<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->default('');
            $table->string('tape_no', 20)->default('')->comment('编号');
            $table->string('type', 6)->default('')->comment('类别');
            $table->string('album', 20)->default('')->comment('专辑');
            $table->string('artist', 128)->default('')->comment('作曲');
            $table->string('co_artist', 128)->default('')->comment('联合创作');
            $table->string('gender', 4)->default('')->comment('性别');
            $table->string('mood', 12)->default('')->comment('情绪');
            $table->string('energy', 12)->default('')->comment('力量');
            $table->string('tempo', 12)->default('')->comment('节奏');
            $table->string('duration', 20)->default('')->comment('时长');
            $table->string('genre', 12)->default('')->comment('曲风');
            $table->string('author', 50)->default('')->comment('作曲');
            $table->string('lyrics', 50)->default('')->comment('作词');
            $table->string('company', 50)->default('')->comment('唱片公司');
            $table->string('sp', 4)->default('')->comment('SP/Ct');
            $table->string('lang', 12)->default('')->comment('语言');
            $table->string('comment', 255)->default('')->comment('备注');
            
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
        Schema::dropIfExists('records');
    }
}
