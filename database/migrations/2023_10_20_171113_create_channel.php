<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChannel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('channel', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 50)->unique()->comment("Guid");
            $table->string('air_date', 20)->comment("具体播出日期时间");
            $table->string('name', 50)->comment("频道名");
            $table->unsignedSmallInteger('status')->default(0)->comment("节目单状态");
            $table->string('comment', 255)->comment("备注")->nullable();
            $table->string('version', 4)->comment('版本号')->nullable();
            $table->string('reviewer', 10)->comment("审核人")->nullable();
            $table->unsignedSmallInteger('audit_status')->default(0)->comment("审核状态");
            $table->string('audit_date', 20)->comment("审核时间")->nullable();
            $table->string('distribution_date', 20)->comment("发单时间")->nullable();
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
        Schema::dropIfExists('channel');
    }
}
