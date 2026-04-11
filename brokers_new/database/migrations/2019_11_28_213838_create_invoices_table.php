<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->double('cost_package');
            $table->double('cost_user');
            
            $table->integer('users')->unsigned()->nullable()->default(0);
            
            $table->string('status');
            $table->string('charge_id')->nullable();
            $table->dateTime('payday')->nullable();
            // $table->dateTime('invoice_date');
            $table->dateTime('due_date');
            $table->bigInteger('company_id')->unsigned();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
