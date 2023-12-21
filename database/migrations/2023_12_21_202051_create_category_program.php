<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryProgram extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('category_program', function (Blueprint $table) {
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('record_id');
            $table->unsignedTinyInteger('type')->comment('类型');
            $table->index(['category_id', 'record_id'], 'category_record');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_program');
    }
}
