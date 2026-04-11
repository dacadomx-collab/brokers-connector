<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected function redirectTo()
    {
        return url('home');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function login(Request $request)
    {
       
        if ( Auth::attempt(['email' => $request->email, 'password' => $request->password]))
        {
            if(!Auth::user()->active)
            {
                Auth::logout();
                return redirect()
                ->back()
                ->withInput($request->only($this->username(), 'remember'))
                ->withErrors(['active' => 'Tu cuenta esta desactivada.']);
                
            }
            //Validar si su correo fue confirmado
            if(!Auth::user()->verified)
            {
                Auth::logout();
                return redirect()->back()->withErrors(['active' => 'Tu cuenta requiere confirmar correo electronico']);
            }
            Auth::logoutOtherDevices($request->password);


            //PA-GA-ME
           //se cancela
            

            
            return $this->sendLoginResponse($request);

            
        }
        else
        {
            return $this->sendFailedLoginResponse($request, 'auth.failed_status');
        }
    }
}
