<?php
/*
Controlador para la api de agentes que solicito el dani,
    Solicitud 
    mandar los agentes dependiendo de la compañia con el api key
-Betun
*/
namespace App\Http\Controllers\ApiController;
use App\Http\Resources\Users as UserResource;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Company;
use App\User;

class AgentApi extends Controller
{
    //
    public function agents(Request $request){
        $validator = Validator::make($request->all(), [
            'api_key' => 'required',
        ]);
        if ($validator->fails()) {
            return  "Te falto la key a mi corazón ";
        }
        $api_key = $request->get('api_key');
        $company = Company::where('api_key', $api_key)->first();
        $users = $company->users()->get();
     
        return UserResource::collection($users);
    }






    public function agent(Request $request){
        $validator = Validator::make($request->all(), [
            'api_key' => 'required',
            'email' => 'required',
        ]);
        if ($validator->fails()) {
            return  "Te falto la key a mi corazón bye";
        }
        $users = User::where('email',$request->email)->first();
        //dd($user);
        $company = Company::where('api_key',$request->api_key)->first();
        //dd($user->company_id);
        //dd($company->id);
        if($users->company_id == $company->id)
        {
            //dd($users->get());
            return  new UserResource(User::find($users->id));
        }else
        {
            return  "Error";
        }
        //dd($user);
    }
}
