<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContactPropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            
            $table->bigInteger('contact_id')->unsigned();
            $table->bigInteger('property_id')->unsigned();
            $table->mediumText('content')->nullable();
            $table->timestamps();
            
            $table->foreign('contact_id')->references('id')->on('contacts');
            $table->foreign('property_id')->references('id')->on('properties');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_properties');
    }
}
