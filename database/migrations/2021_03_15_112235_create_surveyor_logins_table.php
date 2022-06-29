<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveyorLoginsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surveyor_logins', function (Blueprint $table) {
            $table->id();
            $table->integer('surveyor_id');
            $table->string('surveyor_ip',50)->nullable();
            $table->string('location',91)->nullable();
            $table->string('browser',50)->nullable();
            $table->string('os',50)->nullable();
            $table->string('longitude',50)->nullable();
            $table->string('latitude',50)->nullable();
            $table->string('country',50)->nullable();
            $table->string('country_code',50)->nullable();
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
        Schema::dropIfExists('surveyor_logins');
    }
}
