<?php

use App\User;

// =============================================================
// RUTA TEMPORAL — EXTRACCIÓN DE SCHEMA PARA EL CODEX
// SIN middleware. ELIMINAR antes de producción (Mandamiento #8)
// Acceso: http://localhost/brokersconnect_dev/public_html/generar-codex
// =============================================================
Route::get('/generar-codex', function () {
    $tables = DB::select('SHOW TABLES');
    $markdown = "# ESTRUCTURA DE TABLAS EXTRAÍDA PARA EL CODEX\n\n";

    foreach ($tables as $tableObj) {
        $tableName = array_values((array)$tableObj)[0];
        $markdown .= "### Tabla: `{$tableName}`\n";

        $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
        foreach ($columns as $col) {
            $markdown .= "- `{$col->Field}`: [{$col->Type}] - \n";
        }
        $markdown .= "\n";
    }

    return response($markdown)->header('Content-Type', 'text/plain');
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/test-email-invoice', function () {
    $invoice=App\Invoice::findOrFail(60);
    Mail::to(["acadep.webmaster@gmail.com"])->send(new App\Mail\PaymentMail($invoice));

    return "ok";
});

Route::get('/', function () {
    return view('welcome.index');
});

Route::get('/test-confirm-email', function () {
    $user=User::select("*")->first();
      try
        {
            //Enviar correo
            Mail::send("email.confirm-email",["user"=>$user],function ($message) use($user){
                        
                $message->from("pagos@brokersconnector.com");
            
                $message->to("acadep.webmaster@gmail.com");

                $message->subject("Confirmar contrase�0�9a");
            
            });
        }
        catch(\Exception $e)
        {
            print($e);
        }
    return view('email.confirm-email', compact("user"));
});

//Generar cargo
Route::get('/payment', "InvoicesController@payment");

//Confirmar correo
Route::get('/register/confirm/{token}', 'UserController@confirmEmail');

//Republicadores
Route::get('/propiedades-com/feed', 'PortalesController@propiedadesPuntoCom');
Route::get('/lgi/feed', 'PortalesController@laGranInmobiliaria');
Route::get('/doomos/feed', 'PortalesController@doomos');
Route::get('/lamudi/feed', 'PortalesController@lamudi');
Route::get('/casafy/feed', 'PortalesController@casafy');

Route::get('/stock/view/{id}', 'StockController@view')->name('view.stockProperty');

//Politicas de privacidad
Route::get('/privacy-politics', function () {
    return view('welcome.privacy.politics');
});

//Terminos y condiciones
Route::get('/terms-conditions', function () {
    return view('welcome.privacy.terms-conditions');
});

//Pagina publica de la propiedad para compartir en redes
Route::get('/property/info/{id}', "PropertyController@showInfo");


//contact no middleware
Route::post('/home/contact/form', 'ContactController@contactForm')->name('contact.form');

//Route::get('/home/robot', 'InvoicesController@createInvoice')->name('invoices.robot');
Route::get('/success-page', function () {

    return redirect(route('home'));

});

Auth::routes();
// Agrégala preferiblemente después de Auth::routes();
Route::post('/complete-registration', 'Auth\RegisterController@complete')->name('register.complete');

Route::middleware(['auth', 'company','companyPayment'])->group(function () {

    Route::get('/home/property/ficha/create/{id}', 'HomeController@createPage')->name('ficha.create');
    Route::post('/home/property/ficha/preview', 'HomeController@preview')->name('ficha.preview');

    Route::group(['prefix' => '/home/contact-note'], function () {
        Route::post('/store', 'ContactNoteController@store');
    });

    /**
     * Actualizacion rutas
     */
    Route::get('/get-cities', 'CityController@getCitiesById');
    Route::get('/get-districts', 'DistrictController@getDistrictsByCity');

    //Bolsa inmobiliaria
    Route::group(['prefix' => 'stock'], function() {
        //Bolsa inmobiliaria
        Route::get('/index',                'StockController@index')->name('show.all.stock');
        Route::get('/view/company/{id}',    'StockController@viewCompany')->name('view.stockCompany');
        Route::get('/index/search',         'StockController@search')->name('search.stock.index');
        Route::get('/view/company/{id}/search',         'StockController@searchCompany')->name('search.stock.company');
        Route::get('/change-view',  'StockController@ChangeView');
    });

    /**
     * deprecated
     */
    Route::get('/get-states', 'LocationController@getStetesByName');
    Route::get('/get-loc', 'LocationController@getLocByName');
    Route::post('/get-local', 'LocationController@getLocal');

    //Obtener localidades por id
    Route::get('/get-mun-id', 'LocationController@getMunById');
    Route::get('/get-loc-id', 'LocationController@getLocById');
    Route::get('/get-state-id', 'LocationController@getStateById');

    //Propiedades
    Route::group(['prefix' => 'properties'], function() {
        Route::get('/index',            'PropertyController@index')->name('show.all.properties');
        Route::get('/index/search',     'PropertyController@search')->name('search.properties');
        Route::get('/view/{id}',        'PropertyController@view')->name('view.property');;
        Route::get('/print/{id}',        'PropertyController@print')->name('print.property');;
        Route::get('/create',           'PropertyController@create')->name('create.propertie');
        Route::get('/edit/{id}',        'PropertyController@edit')->name('show.edit.properties');
        Route::post('/store',           'PropertyController@store');
        Route::post('/delete',          'PropertyController@delete')->name('delete.properties');
        Route::post('/agent',           'PropertyController@agent');
        Route::post('/state',           'PropertyController@state');
        Route::post('/update',          'PropertyController@update');
        Route::get('/add-images/{id}',  'PropertyController@addImages')->name('add.images.properties');
        Route::post('/emailSend',  'PropertyController@sendEmail')->name('email.send');
        Route::get('/emailpdf/{id}',  'PropertyController@emailpdf')->name('email.pdf');
        Route::get('/email',  'PropertyController@emailView')->name('view.email');
        Route::get('/deletesession',  'PropertyController@deletesession')->name('email.deletesession');
        Route::post('/removeproperty',  'PropertyController@removeProperty')->name('email.removeproperty');
        Route::get('/preview/{id}',  'PropertyController@preview')->name('preview');
        Route::post('/changeStatus',  'PropertyController@add_featured');
        Route::post('/order-images',  'PropertyController@orderImages');
        Route::get('/change-view',  'PropertyController@ChangeView');
        Route::get('/getcitybyfilter',  'PropertyController@getCityByFilter')->name('property.city.filter');
        Route::get('/getstatebyfilter',  'PropertyController@getStateByFilter')->name('property.state.filter');


    });

    //archivos
    Route::group(['prefix' => 'files'], function() {
        Route::post('/upload/store', 'FilePropertyController@store');
        Route::post('/upload/delete', 'FilePropertyController@delete');
        Route::post('/upload/delete-files', 'FilePropertyController@deleteFromArray');
        Route::post('/upload/set_featured', 'FilePropertyController@featured');
        Route::post('/company/store', 'WebSiteFileController@store')->name('website.file.store');
        Route::post('/company/delete', 'WebSiteFileController@delete')->name('website.file.delete');
    });

    //Perfil
    Route::get('/home/profile', 'HomeController@profile')->name('profile');
    Route::post('/update/profile', 'HomeController@updateprofile')->name('update.profile');

    //Contacts
    Route::group(['prefix' => '/home/contact'], function() {
        Route::get('/home', 'ContactController@home')->name('contact.home');
        Route::get('/create', 'ContactController@showCreate')->name('contact');
        Route::post('/create', 'ContactController@create')->name('create.contact');
        Route::get('/{id}/edit', 'ContactController@edit')->name('contact.edit');
        Route::post('/{id}/edit', 'ContactController@update')->name('contact.update');
        Route::get('/{id}','ContactController@showContact')->name('contact.show');
        Route::post('/delete', 'ContactController@delete')->name('contact.delete');
        Route::post('/agent', 'ContactController@updateAgent')->name('contact.agent');

    });


    //Usuarios
    Route::get('/home/users', 'UserController@index')->name('users.index');
    // Route::get('/home/users/profile/{id}', 'UserController@showProfile')->name('users.profile');
    Route::get('/home/users/showcreate', 'UserController@showCreate')->name('users');
    Route::get('/home/users/showedit/{id}', 'UserController@showEdit')->name('users.showEdit');
    Route::post('/home/users/create', 'UserController@create')->name('create.users');
    Route::post('/home/users/delete', 'UserController@delete')->name('delete.users');
    Route::post('/home/users/update', 'UserController@update')->name('update.users');
    Route::post('/home/users/upload-avatar', 'UserController@upload_avatar')->name('upload-avatar.users');


    //Configuración

    Route::get('/home/settings/account', 'HomeController@account')->name('account');


    //website
    Route::get('/home/website', 'settingController@settingWeb')->name('setting.web');
    Route::post('/home/website', 'settingController@websiteUpdate')->name('website.update');

     //Invoices
     Route::get('/home/invoices', 'InvoicesController@invoices')->name('invoices');
     Route::get('/home/invoices/{invoice}', 'InvoicesController@invoice')->name('invoices.view');
     //Route::get('/home/robot', 'InvoicesController@createInvoice')->name('invoices.robot');
     Route::get('/home/invoices/{invoice}/pay', 'InvoicesController@openPay_pay')->name('invoices.pay');
     Route::post('/home/invoices/{invoice}/payment', 'InvoicesController@openPay_payment')->name('invoices.payment');
     Route::get('/home/invoices/{invoice}/paynet', 'InvoicesController@openPay_paynet')->name('invoices.paynet');
     Route::get('/home/invoices/{invoice}/spei', 'InvoicesController@openPay_spei')->name('invoices.spei');
     //Plans
     Route::get('/home/plans', 'CompanyController@plans')->name('view.plans');
     Route::post('/home/plans/edit', 'CompanyController@editPlan')->name('edit.plans');
});


Route::middleware(['auth'])->group(function () {
    Route::get('/home', 'HomeController@index')->name('home')->middleware('companyPayment');
    //Cuenta

    Route::post('/update/company', 'CompanyController@update')->name('update.company');

    Route::post('/store/company', 'CompanyController@store')->name('store.company');

    //ruta cuenta suspendida
    Route::get('/suspended', 'HomeController@suspended')->name('suspended');
    // Ruta para VER la página
    Route::get('/complete-register', 'HomeController@completeRegister')->name('complete.register');

    // Ruta para PROCESAR el botón (POST)
    Route::post('/complete-register', 'HomeController@processCompleteRegister')->name('complete.register.post');
});

