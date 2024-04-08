<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgreement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agreement', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('');
            $table->date('start_at')->comment("具体播出起始日期");
            $table->date('end_at')->comment("具体播出结束日期");
            $table->unsignedTinyInteger('status')->comment('状态')->default(0);
            $table->string('comment', 255)->comment("备注")->nullable();
            $table->timestamps();
        });

        Schema::table('expiration', function (Blueprint $table) {
            $table->unsignedTinyInteger('agreement_id')->after('id')->default(0)->comment('合同ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('agreement');
    }
}
