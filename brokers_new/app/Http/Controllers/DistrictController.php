<?php

namespace App\Http\Controllers;
use App\District;
use DB;


use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function getDistrictsByCity(Request $request){
        $res = DB::table('districts')->selectRaw("id, name as text")->where("city_id", $request->city)->where("name", 'like', '%'.$request->q.'%')->orderBy("text")->limit(10)->get()->toArray();;

        return response(["results"=>$res]);
    }
}
