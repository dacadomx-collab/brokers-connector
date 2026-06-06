<?php

use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use App\PropertyStatus;
use App\PropertyType;
use App\Property;
use App\Feature;
use App\Http\Resources\PropertyStatus as PropertyStatusResource;
use App\Http\Resources\PropertyType as PropertyTypeResource;
use App\Http\Resources\Feature as FeatureResource;
// use App\Http\Resources\UserCollection;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/getproperties', 'ApiController\PropertyApi@getproperties');
Route::get('/getproperties', 'ApiController\PropertyApi@getproperties');
Route::get('/property', 'ApiController\PropertyApi@getproperty');
Route::post('/invoice/paynet/pay', 'ApiController\InvoiceApi@webhook_paynet');

//Api para mostrar resultados 
Route::get('/getpropertiesgeneral', 'ApiController\PropertyApi@getpropertiesgeneral');

Route::group(['prefix' => 'auth'], function () {

    Route::post('login', 'Auth\AuthController@login')->name('login');
    // Route::post('register', 'Auth\AuthController@register');
    Route::group(['middleware' => 'auth:api'], function() {
        Route::get('logout', 'Auth\AuthController@logout');
        // Route::get('user', 'Auth\AuthController@user');
    });
});

Route::get('/properties','ApiController\PropertyApi@properties')->name('get.properties');
// Route::get('/property','ApiController\PropertyApi@property')->name('get.property');

Route::get('/agents', 'ApiController\AgentApi@agents')->name('agents');
Route::get('/agent', 'ApiController\AgentApi@agent')->name('agent');

//Correo electronico
Route::post('/email','emailController@send');

//Propiedades
Route::group(['prefix' => 'property'], function() {
    Route::get('/types', function () {
        return PropertyTypeResource::collection(PropertyType::all());
    });
    Route::get('/statuses', function () {
        return PropertyStatusResource::collection(PropertyStatus::all());
    });
    Route::get('/search-params', function () {
        return response()->json([

            'statuses' => PropertyStatusResource::collection(PropertyStatus::luly()->get()),
             'types' => PropertyTypeResource::collection(PropertyType::luly()->get()),
        ]);
    });

    Route::get('/search-luly', function () {
        return response()->json([
            'statuses' => PropertyStatusResource::collection(PropertyStatus::all()),
             'types' => PropertyTypeResource::collection(PropertyType::all()),
        ]);
    });
});

//Account
Route::group(['prefix' => 'account'], function() {
    Route::get('/data', 'ApiController\AccountController@getData')->name('acount.logo');
});


Route::get('/getfeatures', function () {
    return FeatureResource::collection(Feature::orderBy('name')->get());
});



// IA / Chat — requiere Bearer Token (Passport)
Route::group(['prefix' => 'ai', 'middleware' => ['auth:api', 'throttle:15,1']], function () {
    Route::post('/chat', 'AiChatController@sendMessage');
});


// V2 Bridge — token de un solo uso como mecanismo de autenticación
Route::prefix('v2')->group(function () {

    // Broker Brain IA — Motor de Tasación y CMA. Auth: Bearer session_token (Cache, no Passport).
    Route::get('/broker-brain/my-properties', 'Api\BrokerBrainController@myProperties')->middleware('throttle:30,1');
    Route::post('/broker-brain/cma',          'Api\BrokerBrainController@cma')->middleware('throttle:10,1');

    Route::get('/bridge/validate',  'Api\V2BridgeController@bridgeValidate');
    Route::post('/subscriptions',   'Api\V2BridgeController@subscribe');

    // Radar de Plusvalía — Auth: Bearer session_token (Cache, patrón Bridge V2)
    Route::get('/radar/heatmap',        'Api\V2RadarController@heatmap')->middleware('throttle:30,1');
    Route::get('/radar/zone/{zipcode}', 'Api\V2RadarController@zoneDetail')->middleware('throttle:60,1');

    // BrokerPulse AI — Reportes e Informes con IA. Auth: Bearer session_token (Cache, patrón Bridge V2).
    Route::get('/analytics/validate',         'Api\AiAnalyticsController@bridgeAnalyticsValidate');
    Route::post('/analytics/query',           'Api\AiAnalyticsController@query')->middleware('throttle:10,1');

    // ACADEP AURA — Handshake / Test de Conexión. Auth: Passport OAuth2 (super_admin).
    Route::post('/analytics/test-connection', 'Api\AiAnalyticsController@testAcadepConnection')
        ->middleware(['auth:api', 'role:super_admin', 'throttle:5,1']);

    // Regla de monetización — accesible por Admins (auth:api)
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/my-company/user-quota', 'Api\UserQuotaController@check');
    });

    // ── Panel Super Admin — Passport OAuth2 nativo (auth:api + role:super_admin + empresa autorizada) ──
    Route::prefix('admin')->middleware(['auth:api', 'role:super_admin', 'super_admin_company', 'throttle:30,1'])->group(function () {
        // Gestión de usuarios Admin / super_admin
        Route::get('/users',                          'Api\SuperAdminController@listAdmins');
        Route::post('/users/{id}/toggle-role',        'Api\SuperAdminController@toggleRole');
        Route::post('/users/{id}/reset-password',     'Api\SuperAdminController@resetPassword');

        // Orquestador IA — CRUD de proveedores (ai_settings)
        Route::get('/ai-settings',                    'Api\SuperAdminController@listAiSettings');
        Route::post('/ai-settings',                   'Api\SuperAdminController@storeAiSetting');
        Route::put('/ai-settings/{id}',               'Api\SuperAdminController@updateAiSetting');
        Route::delete('/ai-settings/{id}',            'Api\SuperAdminController@destroyAiSetting');
        Route::patch('/ai-settings/{id}/toggle',      'Api\SuperAdminController@toggleAiSetting');
        Route::post('/ai-settings/{id}/test',         'Api\SuperAdminController@testAiSetting');

        // Motor de Pagos — CRUD de pasarelas (payment_gateway_settings)
        Route::get('/payment-gateways',                       'Api\PaymentGatewayController@index');
        Route::post('/payment-gateways',                      'Api\PaymentGatewayController@store');
        Route::put('/payment-gateways/{id}',                  'Api\PaymentGatewayController@update');
        Route::delete('/payment-gateways/{id}',               'Api\PaymentGatewayController@destroy');
        Route::patch('/payment-gateways/{id}/toggle',         'Api\PaymentGatewayController@toggle');
        Route::patch('/payment-gateways/{id}/toggle-sandbox', 'Api\PaymentGatewayController@toggleSandbox');

        // Gestión de Empresas — SaaS CRM del Super Admin
        Route::get('/companies',                                    'Api\SuperAdminController@listCompanies');
        Route::put('/companies/{id}',                               'Api\SuperAdminController@updateCompany');
        Route::patch('/companies/{id}/toggle-status',               'Api\SuperAdminController@toggleCompanyStatus');
        Route::patch('/companies/{id}/subscription-override',       'Api\SuperAdminController@subscriptionOverride');
        Route::delete('/companies/{id}',                            'Api\SuperAdminController@deleteCompany');
        Route::get('/companies/{id}/validate-user-quota',           'Api\SuperAdminController@validateUserQuota');

        // Audit Trail
        Route::get('/audit-logs',                                   'Api\SuperAdminController@listAuditLogs');

        // Monitor de Consumo IA — Registro Maestro de Tokens por Tenant
        Route::get('/token-stats',                                  'Api\SuperAdminController@tokenStats');

        // Synaptic Core™ — Prompts Maestros del Motor Cognitivo AVM
        Route::get('/ai-prompts',                    'Api\SuperAdminController@listAiPrompts');
        Route::put('/ai-prompts/{slug}',             'Api\SuperAdminController@updateAiPrompt');
        Route::post('/ai-prompts/{slug}/test',       'Api\SuperAdminController@testAiPrompt');

        // Purga de Proveedores IA — borrado físico con limpieza de caché
        Route::delete('/ai-providers/{id}',          'Api\AiAnalyticsController@deleteAiProvider');

        // Telemetría estática de ACADEP — lee .env del servidor (sin acceder a ai_settings)
        Route::get('/acadep/status',                 'Api\AiAnalyticsController@acadepStatus');

        // Utilería de rotación: genera hash bcrypt del ACADEP_AURA_KEY y construye el SQL de sincronización
        Route::post('/acadep/generate-query',        'Api\AiAnalyticsController@generateAcadepSyncQuery');
    });
});

//API APP MOBILE

Route::group(['prefix' => 'mobile'], function() {

    //Route::get('/properties','Mobile\PropertyController@index')->name('get.properties');
    Route::group(['middleware' => ['auth:api']], function () {
        
        Route::resource('properties', 'Mobile\PropertyController');
    });

});
