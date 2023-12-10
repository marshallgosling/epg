<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EditRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('records', function(Blueprint $table) {
            $table->unsignedInteger('seconds')->after('duration')->default(0)->index('seconds');
        });

        Schema::table('program', function(Blueprint $table) {
            $table->unsignedInteger('seconds')->after('duration')->default(0)->index('seconds');
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
