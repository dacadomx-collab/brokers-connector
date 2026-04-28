<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOpenpaySubscriptionIdToCompaniesTable extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // ID de la suscripción activa en OpenPay — permite gestión del ciclo de facturación recurrente.
            $table->string('openpay_subscription_id', 64)->nullable()->after('openpay_customer_id');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('openpay_subscription_id');
        });
    }
}
