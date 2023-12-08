<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditMaterial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('material', function(Blueprint $table) {
            $table->string('group', 50)->after('category')->nullable();
            $table->string('md5', 32)->after('frames')->nullable();
            $table->string('channel', 10)->after('duration')->nullable();
            $table->string('filepath', 10)->after('size')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
