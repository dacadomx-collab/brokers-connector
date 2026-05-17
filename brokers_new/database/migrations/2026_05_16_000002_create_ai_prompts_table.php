<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAiPromptsTable extends Migration
{
    public function up()
    {
        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->increments('id');
            // 'key' es palabra reservada en MySQL 8.0 — se usa 'slug' para evitar
            // errores de SQL. El API expone el campo como 'slug'.
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->text('prompt_text');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_prompts');
    }
}
