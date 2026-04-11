<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;


class CityController extends Controller
{
    public function getCitiesById(Request $request){

        $res = DB::table('cities')->selectRaw("id, name as text")->where("state_id", $request->state)->where("name", 'like', '%'.$request->q.'%')->orderBy("text")->limit(10)->get()->toArray();;

        return response(["results"=>$res]);
    }
}
