<?php

namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Mail\ConfirmPasswordResetEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\User;


class ForgotPasswordController extends Controller
{

   
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
 

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function sendResetLinkEmail(Request $request)
{
    $this->validateEmail($request);
    $email = $request->email;
    $user =  User::where('email', $email)->first();
    if($user){
        $token = Password::createToken($user);
        Mail::to($email)->send(new ConfirmPasswordResetEmail($token, $email));
    }
    return back()->with(['status' => 'success']);
}

    // public function index(Request $request){
    //     return view('auth.passwords.email');
    // }
}
