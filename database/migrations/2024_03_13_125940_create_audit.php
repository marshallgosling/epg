<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAudit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20)->comment("名称");
            $table->unsignedTinyInteger('status')->default(0)->comment('状态');
            $table->text('reason')->nullable()->comment('原因');
            $table->unsignedInteger('channel_id')->default(0)->comment('编单ID');
            $table->string('admin', 100)->nullable()->comment('管理员');
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
        Schema::dropIfExists('audit');
    }
}
