<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;


use App\Property;


class PortalesController extends Controller
{
    
    public function propiedadesPuntoCom(){
        $properties = getProperties();
        $content = View::make('portales.propiedades', compact('properties'));
        return Response::make($content, '200')->header('Content-Type', 'text/xml');
    }

    public function laGranInmobiliaria(){
        $properties = getProperties();
        $content = View::make('portales.la-gran-inmobiliaria', compact('properties'));
        return Response::make($content, '200')->header('Content-Type', 'text/xml');
    }

    public function casafy(){
        $properties = getProperties();
        $content = View::make('portales.casafy', compact('properties'));
        return Response::make($content, '200')->header('Content-Type', 'text/xml');
    }

    public function doomos(){
        $properties = getProperties();
        $content = View::make('portales.doomos', compact('properties'));
        return Response::make($content, '200')->header('Content-Type', 'text/xml');
    }

    public function lamudi(){
        $properties = getProperties();
        
            // dd($properties[5]->company);
        
        $content = View::make('portales.lamudi', compact('properties'));
        return Response::make($content, '200')->header('Content-Type', 'text/xml');
    }
}

function getProperties(){
    $query = Property::published();

    //revisamos si existe en las categorias no-luly
    $query->whereIn('prop_use_id', [12,2,5]);
    // dd($query->get());

    //revisamos si existe en los tipos no-luly
    $query->whereIn('prop_type_id', [1,2,4,9,11,18,20,23,27,28,29,30]);
    //dd($query->count());
    //revisamos si existe en las operaciones no-luly
    $query->whereIn('prop_status_id', [1,2,8]);
    
    return $query->get();
}