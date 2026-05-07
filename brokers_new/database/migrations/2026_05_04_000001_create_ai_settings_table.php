<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('provider_name');
            $table->text('api_key');
            $table->json('extra_config')->nullable();
            $table->tinyInteger('priority_order')->unsigned()->default(1);
            $table->boolean('is_active')->default(1);

            // nullable → null = configuración global; valor = override por tenant
            $table->bigInteger('company_id')->unsigned()->nullable();
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_settings');
    }
}
