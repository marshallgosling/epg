<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRawFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename', 200);
            $table->unsignedTinyInteger('folder_id')->default(0);
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('unique_no', 30)->nullable();
            $table->string('name', 200)->nullable();
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
        Schema::dropIfExists('raw_files');
    }
}
