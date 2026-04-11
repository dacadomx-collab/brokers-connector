<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFeaturePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('feature_properties', function (Blueprint $table) {
            $table->bigInteger('feature_id')->unsigned();
            $table->bigInteger('property_id')->unsigned();

            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');;
            $table->foreign('property_id')->references('id')->on('properties');
            $table->primary(['feature_id', 'property_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('feature_properties');
    }
}
