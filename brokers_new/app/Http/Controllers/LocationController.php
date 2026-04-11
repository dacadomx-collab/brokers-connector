<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class LocationController extends Controller
{
    public function getStetesByName(Request $request)
    {
        
        $res=DB::table('estados')->selectRaw("id, nombre as text")->where("nombre", 'like', '%'.$request->q.'%')->orderBy("text")->limit(10)->get()->toArray();

        return response(["results"=>$res], 200);
    }

    public function getMunByName(Request $request)
    {
        
        $res=DB::table('municipios')->selectRaw("id, nombre as text")->where("nombre", 'like', '%'.$request->q.'%')->where("estado_id",$request->state)->orderBy("text")->limit(5)->get()->toArray();

        return response(["results"=>$res], 200);
    }

    public function getLocByName(Request $request)
    {
        $res=DB::table('localidades')->selectRaw("id, nombre as text")->where("nombre", 'like', '%'.$request->q.'%')->where("municipio_id",$request->mun)->orderBy("text")->limit(5)->get()->toArray();;

        return response(["results"=>$res], 200);
    }



    public function getLocal(Request $request)
    {
        $res=DB::table('localidades')->where("id",$request->id)->get();

        return response((array) $res[0], 200);
    }

    //Obtener localidades por id
    
    public function getMunById(Request $request)
    {
        
        $res=DB::table('cities')->selectRaw("id, name as text")->where("id", $request->id)->first();

       
        return response((array) $res, 200);
    }

    public function getLocById(Request $request)
    {
        $res=DB::table('districts')->selectRaw("id, name as text")->where("id", $request->id)->first();

        return response((array) $res, 200);
    }

    public function getStateById(Request $request)
    {
     
        $res=DB::table('states')->selectRaw("id, name as text")->where("id", $request->id)->first();

        return response((array) $res, 200);
    }
}
