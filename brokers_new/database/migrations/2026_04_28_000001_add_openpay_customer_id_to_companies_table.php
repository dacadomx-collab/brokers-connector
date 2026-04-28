<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOpenpayCustomerIdToCompaniesTable extends Migration
{
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            // ID del cliente en OpenPay — permite trazabilidad, tarjetas guardadas y cargos recurrentes.
            $table->string('openpay_customer_id', 64)->nullable()->after('active');
        });
    }

    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('openpay_customer_id');
        });
    }
}
