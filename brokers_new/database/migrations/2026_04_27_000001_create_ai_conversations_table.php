<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // TENANT LOCK — Mandamiento #2: aislamiento estricto por company_id
            $table->bigInteger('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies');
            $table->index('company_id');

            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('title');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_conversations');
    }
}
