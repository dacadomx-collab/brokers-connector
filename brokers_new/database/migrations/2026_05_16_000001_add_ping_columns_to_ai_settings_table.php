<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPingColumnsToAiSettingsTable extends Migration
{
    public function up()
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->timestamp('last_tested_at')->nullable()->after('company_id');
            $table->string('last_test_status', 10)->nullable()->after('last_tested_at');
            $table->unsignedInteger('last_test_latency_ms')->nullable()->after('last_test_status');
            $table->text('last_test_error')->nullable()->after('last_test_latency_ms');
        });
    }

    public function down()
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn([
                'last_tested_at',
                'last_test_status',
                'last_test_latency_ms',
                'last_test_error',
            ]);
        });
    }
}
