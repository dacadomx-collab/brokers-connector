<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Validator;
use Exception;
use App\FileProperty;
use App\Property;
use App\Company;
use File;

class WebSiteFileController extends Controller
{
    //

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' =>'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover' =>'image|mimes:jpeg,png,jpg,gif,svg|max:8048',
            'team' =>'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'banner' =>'image|mimes:jpeg,png,jpg,gif,svg|max:8048',
        ]);

        if ($validator->fails()) {
            return response()->json(["type_msg"=>1,"msg"=>"Archivo no valido, revisa la extensión del archivo y no mayor a 2mb"],401);
        }
        $companyId = auth()->user()->company->id;
        $company=Company::where("id", $companyId)->first();
        $set_featured=false;
        $type = "";
        $image= "";
        if($request->file('logo'))
        {
            $image = $request->file('logo');
            $type = "logo";
        }
        else if($request->file('cover')){
        
            $image = $request->file('cover');
            $type = "cover";
        }
        else if($request->file('banner')){
        
            $image = $request->file('banner');
            $type = "banner";
        }
        else if($request->file('team')){
        
            $image = $request->file('team');
            $type = "team";
        }
        else {
            return response()->json(["type_msg"=>1,"msg"=>"Archivo no valido, revisa la extensión del archivo y no mayor a 2mb"],401);
        }
        $previous_image = $company->$type;
        if($previous_image)
        {
            File::delete(public_path().$previous_image);
        }
        $size=($image->getSize())/1000000;
        $path = 'companies/'.$companyId.'/';
        $extension = $image->getClientOriginalExtension();
        //$name_image_no_extension = $image->getClientOriginalName();
       
        if($request->files->count() > 1)
        {
            return response(["type_msg"=>1,"msg"=>"No se permiten más de 1 imágen"], 401);
        }

        $imageName = uniqid($type).'.'.$extension;
        try {
            $moved = $image->move(public_path($path), $imageName);
        } catch (\Throwable $th) {
            throw new Exception("Error al subir imagen, intente mas tarde", 1);
        }
        $url=$path.$imageName;
        $company->$type = '/'.$url;
        $company->save();
        return response()->json([
            "type_msg"=>2,
            "image"=>'/'.$url, 
            "type" => $type
            ]);
    }

}
