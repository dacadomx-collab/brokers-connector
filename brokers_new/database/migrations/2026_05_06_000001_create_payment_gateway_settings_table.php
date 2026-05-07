<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaymentGatewaySettingsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider_name', 50);
            $table->boolean('is_active')->default(false);
            $table->boolean('is_sandbox')->default(true);   // sandbox por defecto — más seguro
            $table->text('credentials')->nullable();         // JSON encriptado
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
}
