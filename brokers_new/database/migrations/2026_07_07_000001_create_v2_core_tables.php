<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateV2CoreTables extends Migration
{
    public function up()
    {
        // Catálogo global — padrón SETUES BCS. Sin company_id: el folio pertenece
        // a la persona/broker, no a una agencia. Poblada por el comando artisan
        // setues:sync (Fase 2), nunca por un endpoint HTTP de escritura.
        Schema::create('government_licenses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('folio_licencia', 50)->unique();
            $table->string('nombre_titular', 255);
            $table->string('razon_social', 255)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('telefono', 100)->nullable();
            $table->text('domicilio')->nullable();
            $table->string('foto_url', 555)->nullable();
            $table->string('status_oficial', 50)->default('Activa');
            $table->timestamp('last_sync');
        });

        // Catálogo global — directorio AMPI/ASPI. Vinculado al folio SETUES, no a company_id.
        Schema::create('association_members', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('folio_licencia', 50);
            $table->foreign('folio_licencia')->references('folio_licencia')->on('government_licenses')->onDelete('cascade');
            $table->enum('association_name', ['AMPI La Paz', 'AMPI Los Cabos', 'AMPI Cabo Bahia Sur', 'AMPI Loreto', 'ASPI']);
            $table->string('member_name', 255);
            $table->string('status', 50)->default('Activo');
            $table->timestamp('last_sync');
        });

        // Cross-tenant por diseño: el arbitraje espacial compara propiedades de
        // DOS compañías distintas. Resolución de Riesgos #1 (Informe Forense
        // 2026-07-06): la vista de la Compañía A solo debe exponer id/coordenadas/
        // fecha de la propiedad en conflicto — nunca datos del dueño/documentos/agente
        // de la Compañía B. Esa regla se aplica en el Resource/Controller, no aquí.
        // Sin onDelete en las FK: es un registro de auditoría/legal, no debe
        // desaparecer si se borra una compañía o propiedad involucrada.
        Schema::create('property_controversies', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('clashing_company_id');
            $table->foreign('clashing_company_id')->references('id')->on('companies');

            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')->references('id')->on('properties');

            $table->unsignedBigInteger('clashing_property_id');
            $table->foreign('clashing_property_id')->references('id')->on('properties');

            $table->enum('status', ['pendiente', 'en_revision', 'resuelta', 'legal_review'])->default('pendiente');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });

        // TENANT LOCK estricto — Módulo MAIA (Arbitraje Semántico Documental).
        Schema::create('property_documents_metadata', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies');
            $table->index('company_id');

            $table->unsignedBigInteger('property_id');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');

            $table->enum('document_type', ['titulo_propiedad', 'libertad_gravamen', 'contrato_exclusividad', 'valuacion_catastral']);
            $table->string('file_path', 555);
            $table->enum('ai_validation_status', ['en_revision', 'validado', 'rechazado'])->default('en_revision');
            $table->string('extracted_owner_name', 255)->nullable();
            $table->text('extracted_address')->nullable();
            $table->date('contract_expiration')->nullable();
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->json('raw_ai_analysis')->nullable();
            $table->timestamps();
        });

        // Módulo MAIA — pHash anti-duplicados (Fricción Arquitectónica #2 del Informe Forense).
        Schema::table('file_properties', function (Blueprint $table) {
            $table->string('phash', 255)->nullable()->after('type');
        });

        // Módulo MAIA — Arbitraje Espacial. lat/lng existentes son VARCHAR (Fricción
        // Arquitectónica #1); estas columnas DECIMAL permiten cálculos de distancia
        // eficientes sin migrar/romper los campos VARCHAR legacy en uso.
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('lat_decimal', 10, 8)->nullable()->after('lat');
            $table->decimal('lng_decimal', 10, 8)->nullable()->after('lng');
        });
    }

    public function down()
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['lat_decimal', 'lng_decimal']);
        });

        Schema::table('file_properties', function (Blueprint $table) {
            $table->dropColumn('phash');
        });

        Schema::dropIfExists('property_documents_metadata');
        Schema::dropIfExists('property_controversies');
        Schema::dropIfExists('association_members');
        Schema::dropIfExists('government_licenses');
    }
}
