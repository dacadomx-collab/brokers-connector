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
Route::group(['prefix' => 'ai', 'middleware' => ['auth:api']], function () {
    Route::post('/chat', 'AiChatController@sendMessage');
});

// V2 Bridge — token de un solo uso como mecanismo de autenticación
Route::prefix('v2')->group(function () {
    Route::get('/bridge/validate',  'Api\V2BridgeController@bridgeValidate');
    Route::post('/subscriptions',   'Api\V2BridgeController@subscribe');
});

//API APP MOBILE

Route::group(['prefix' => 'mobile'], function() {

    //Route::get('/properties','Mobile\PropertyController@index')->name('get.properties');
    Route::group(['middleware' => ['auth:api']], function () {
        
        Route::resource('properties', 'Mobile\PropertyController');
    });

});
