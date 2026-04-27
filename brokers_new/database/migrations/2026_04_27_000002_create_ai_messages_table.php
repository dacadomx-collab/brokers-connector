<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->bigInteger('conversation_id')->unsigned();
            $table->foreign('conversation_id')
                  ->references('id')
                  ->on('ai_conversations')
                  ->onDelete('cascade');

            $table->enum('role', ['user', 'assistant', 'system']);
            $table->longText('content');
            $table->integer('tokens_used')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_messages');
    }
}
