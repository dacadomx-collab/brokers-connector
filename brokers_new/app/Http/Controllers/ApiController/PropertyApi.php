<?php
/*
Controlador para la Api de las paginas web Utilizadas para cada compaia 
la unica funcion para el dia 18/10/2019 es mostrar las propiedades por solicitud filtrando con limite

-Betun
*/
namespace App\Http\Controllers\ApiController;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Property as PropertyResource;
use App\Property;
use App\Company;
use Illuminate\Support\Facades\Validator;

class PropertyApi extends Controller
{
        
    public function property(Request $request){
        $validator = Validator::make($request->all(), [
            // 'property_id' => 'numeric',
            'api_key' => 'required',
            'limit' => 'numeric',
            'order' => 'string',
            // 'title' => '',
            // 'id' => 'required|numeric',
            'description' => 'boolean',
            'ubication'   => 'boolean',
            'comission'   => 'boolean',
            'sizes'       => 'boolean',
            'images'      => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Consulta no valida, por favor revise la documentación'
            ], 400);
        }

        $property = Property::find($request->id);
        // return $property;

        if (!$property) {
           return response()->json(['message' => 'Not Found!'], 404);
        }else {

          $data = $property->dataAPI($request->all());

            return response()->json($data, 200);
        }
    } 


    public function getProperties(Request $request){  //?api_key=123123&limit=3&order=desc&sizes=1&featured=1
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|exists:companies,api_key',
            'description' => 'nullable | boolean',
            'ubication'   => 'nullable | boolean',
            'comission'   => 'nullable | boolean',
            'sizes'       => 'nullable | boolean',
            'images'      => 'nullable | boolean',
            //query params
            'limit'       => 'nullable | numeric',
            'pricemin'    => 'nullable | numeric',
            'pricemax'    => 'nullable | numeric',
            'free_search' => 'nullable | string',
            'parking_lots'=> 'nullable | numeric',
            
            'baths'       => 'nullable | numeric',
            'bedrooms'    => 'nullable | numeric',
            'type'        => 'nullable | numeric|exists:property_types,id',
            'status'      => 'nullable | numeric|exists:property_statuses,id',

            'paginate'    => 'nullable | numeric',
            'order'       => 'nullable | string',
            'featured'    => 'nullable | boolean',
            'features'    => 'nullable | array',
            'city'    => 'nullable | numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Consulta no valida, por favor revise la documentación'
            ], 400);
        }
  
        $company = Company::where('api_key', $request->api_key)->first();
        
        $properties = Property::getPropertiesFromRequest($request, $company->id);

        return PropertyResource::collection($properties);
    }

    public function getpropertiesgeneral(Request $request)
    {
        // Parametros del request 
        // ?location=&types=&status=&baths=&beds=&min=&max=
        $query=Property::where("bbc_general", true)->where("published", true);

        if($request->location)
        {
            $query=$query->whereHas('local.city', function($q) use ($request){

                $q->where('id',$request->city);

            });
        }
       
        if($request->min)
        {
            $query=$query->where("price", ">=", $request->min);
        }
        
        if($request->max)
        {
            $query=$query->where("price", "<=", $request->max);
        }
        
        if($request->baths)
        {
            $query=$query->where("baths", "=", $request->baths);
        }

        if($request->beds)
        {
            $query=$query->where("bedrooms", "=", $request->rooms);
        }

        if($request->status)
        {
            $query=$query->where("prop_status_id", "=", $request->status);
        }

        if($request->types)
        {
            $query=$query->whereIn("prop_type_id", $request->types);
        }

        return PropertyResource::collection($query->get());

    }

    public function getProperty(request $request){ 
        $validator = Validator::make($request->all(), [
            'api_key' => 'required|exists:companies,api_key',
            'id' => 'required|numeric',
            'description' => 'boolean',
            'ubication'   => 'boolean',
            'comission'   => 'boolean',
            'sizes'       => 'boolean',
            'images'      => 'boolean',
            'features'    => 'nullable | string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Consulta no valida, por favor revise la documentación'
            ], 400);

        }
        $property = Property::findorFail($request->id);

        // dd($property->company());
        if ($property->company->api_key == $request->api_key) {
            return new PropertyResource($property);
        } else {
            return response()->json([
                'error' => '404 Resource not found'
            ], 404);
        }
    }
}

