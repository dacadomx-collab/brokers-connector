<?php

namespace App\Http\Controllers\ApiController;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Account as AccountResource;
use Illuminate\Support\Facades\Validator;

use App\Company;

class AccountController extends Controller
{
    public function getData(Request $request){
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|exists:companies,api_key',
            
            'logo'       => 'boolean',
            'banner'     => 'boolean',
            'cover'      => 'boolean',
            'team'       => 'boolean',
            'about'      => 'boolean',
            'address'    => 'boolean',
            'phone'      => 'boolean',
            'email'      => 'boolean',
            'name'      => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Consulta no valida, por favor revise la documentación'
            ], 400);
        }

        $company = Company::where('api_key', $request->api_key)->first();

        return new AccountResource($company);

    
        }

        

        

        

       
    
}
