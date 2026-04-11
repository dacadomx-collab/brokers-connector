<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Company;
use App\User;
class emailController extends Controller
{
    //enviar email desde las paginas
    public function send(Request $request){
    $validator = Validator::make($request->all(), [
        'api_key' => 'required',
        'email' => 'required|email',
        'name' => 'required',
        'subject' => 'required',
        'message' => 'required'
    ]);
    if ($validator->fails()) {
        return response()->json([
            'error' => 'bad request'
        ], 400);
    }
    //Checamos que el api_key 
    $api_key = $request->get('api_key');
    $company = Company::where('api_key', $api_key)->first();
    if($company == null)
    {
        abort(403, 'Unauthorized action.');
    }
    $company_name = $company->name;
       $email = new \SendGrid\Mail\Mail(); 
       $email->setFrom("correos@brokersconnector.com", "Has recibido un nuevo correo desde tu página");
       $email->setReplyto($request->email);
       $email->setSubject($request->subject);
       $email->addTo($company->email, $company->name);
       $email->addContent(
           "text/html", strval(view('email.email')->with(compact('request', 'company_name')))
       );
       $sendgrid = new \SendGrid('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');
       try {
           $response = $sendgrid->send($email);
       } catch (Exception $e) {
           return 'Caught exception: '. $e->getMessage() ."\n";
       }

       return response()->json([
        'succesfull' => 'correo enviado'
    ], 200);
      
    }
}
