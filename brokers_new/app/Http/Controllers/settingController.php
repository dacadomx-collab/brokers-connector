<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class settingController extends Controller
{
    //Controlador para las configuraciones de el modulo configuración.
    public function settingWeb(){
        $company = auth()->user()->company;

    if ($company->website_config) {
        $data = json_decode($company->website_config);
    } else {
        $data = null;
    }

        return view("website.setting", compact('company','data'));
    }


    public function websiteUpdate(Request $request){

        $data = Arr::only($request->all(), ['main_color', 'title_color', 'name', 'keywords']);

      //  return json_encode($data);

        $company = auth()->user()->company;
        $company->website_config = json_encode($data);
        $company->about = $request->about;
        $company->save();
        
        return back();

    }

  
}
