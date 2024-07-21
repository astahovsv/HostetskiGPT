<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveOldSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('hostetskigpt');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('hostetskigpt', function (Blueprint $table) {
            $table->integer('mailbox_id')->primary();
            $table->boolean('enabled');
            $table->string('api_key');
            $table->integer('token_limit');
            $table->text('start_message');
            $table->string('model');
            $table->boolean('client_data_enabled');
        });
    }
}
