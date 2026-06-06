<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, SparkPost and others. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    // ACADEP AURA — Nodo soberano de IA local
    'acadep' => [
        'url_lan'  => env('ACADEP_AURA_URL_LAN', ''),
        'url_wan'  => env('ACADEP_AURA_URL_WAN', ''),
        'key'      => env('ACADEP_AURA_KEY', ''),
        'agent_id' => 'AURA_BKC_V1',
    ],

    // Control de acceso Super Admin — sobrevive config:cache
    'super_admin_company_ids' => env('SUPER_ADMIN_COMPANY_IDS', ''),

    // URLs del ecosistema V2 (Bridge + SPAs) — sobreviven config:cache
    'v2_frontend_base' => env('V2_FRONTEND_BASE', ''),
    'v2_api_base'      => env('V2_API_BASE',      ''),

];
