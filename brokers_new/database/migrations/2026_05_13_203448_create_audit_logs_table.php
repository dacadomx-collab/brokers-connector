<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_email', 191)->nullable();
            // Acciones: activate | suspend | delete | toggle_role | reset_password
            $table->string('action', 50);
            $table->string('target_type', 50)->default('company');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name', 191)->nullable();
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
            $table->index('actor_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
