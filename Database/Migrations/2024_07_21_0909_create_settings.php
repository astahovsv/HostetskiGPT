<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Migration_2024_07_21_0909 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gptassistant_settings', function (Blueprint $table) {
            $table->integer('mailbox_id')->primary();
            $table->string('api_key');
            $table->string('assistant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gptassistant_settings');
    }
}
