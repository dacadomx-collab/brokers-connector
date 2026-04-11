<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('api_key', 80)->nullable()->unique();
            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->string('rfc');
            $table->string('colony');
            $table->string('zipcode');
            $table->string('email');
            $table->integer('package')->nullable();
            $table->dateTime('cutoff_date')->nullable();
            $table->string('dominio')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('cover')->nullable();
            $table->string('team')->nullable();
            $table->text('about')->nullable();
            $table->text('website_config')->nullable();
            $table->bigInteger('owner')->unsigned();
            $table->integer('active')->unsigned();
            // $table->string('plan');1
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
        Schema::dropIfExists('companies');
    }
}
