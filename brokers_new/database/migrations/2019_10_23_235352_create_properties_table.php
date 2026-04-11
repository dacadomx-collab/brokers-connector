<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->MediumText('title');
            $table->MediumText('title_en')->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_en')->nullable();
            $table->integer('bedrooms')->nullable()->default(0);
            $table->integer('baths')->nullable()->default(0);
            $table->integer('floor')->nullable();
            $table->integer('floor_located')->nullable();
            $table->integer('medium_baths')->nullable()->default(0);
            $table->integer('parking_lots')->nullable()->default(0);
            $table->double('total_area')->nullable()->default(0);
            $table->double('built_area')->nullable()->default(0);
            $table->double('price');
            $table->unsignedInteger('currency');
            
            $table->mediumText('local_id')->nullable();
            $table->string('lng')->nullable();
            $table->string('lat')->nullable();
            $table->string('address')->nullable();
            $table->string('commission')->nullable();
            $table->unsignedInteger('type_commission')->nullable();
            $table->bigInteger('featured_image')->unsigned()->nullable();
            $table->double('length')->nullable()->default(0);
            $table->double('front')->nullable()->default(0);
            $table->integer('antiquity')->nullable();
            $table->longText('key')->nullable();
            $table->boolean('published')->default(true);
            $table->boolean('featured')->default(false);
            $table->string('video')->nullable();

            $table->boolean('bbc_general')->default(false)->nullable();
            $table->boolean('bbc_aspi')->default(false)->nullable();
            $table->boolean('bbc_ampi')->default(false)->nullable();

            $table->string('exterior', 100)->nullable()->default('s/n');
            $table->string('interior', 100)->nullable()->default('s/n');
            $table->unsignedInteger('zipcode')->nullable();

            
            $table->bigInteger('company_id')->unsigned();
            $table->bigInteger('prop_status_id')->unsigned();
            $table->bigInteger('prop_type_id')->unsigned();
            $table->bigInteger('prop_use_id')->unsigned()->nullable();
            $table->bigInteger('agent_id')->unsigned()->nullable();
            $table->bigInteger('created_by')->unsigned();

            $table->foreign('prop_status_id')->references('id')->on('property_statuses');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->foreign('prop_type_id')->references('id')->on('property_types');
            $table->foreign('prop_use_id')->references('id')->on('property_uses');
            $table->foreign('agent_id')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');

            $table->softDeletes();
            
            
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
        Schema::dropIfExists('properties');
    }
}
