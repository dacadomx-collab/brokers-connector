<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Property;
use App\Company;
use App\Http\Resources\Property as PropertyResource;


class PropertyController extends Controller
{
    public function index(Request $request){  
        $validator = Validator::make($request->all(), [
            
            'description' => 'nullable | boolean',
            'ubication'   => 'nullable | boolean',
            'comission'   => 'nullable | boolean',
            'sizes'       => 'nullable | boolean',
            'images'      => 'nullable | boolean',
            
            'limit'       => 'nullable | numeric',
            'pricemin'       => 'nullable | numeric',
            'pricemax'       => 'nullable | numeric',
            'free_search' => 'nullable | string',
            'parking_lots' => 'nullable | numeric',
            
            'baths'       => 'nullable | numeric',
            'bedrooms'    => 'nullable | numeric',
            'type'        => 'nullable | numeric|exists:property_types,id',
            'status'   => 'nullable | numeric|exists:property_statuses,id',

            'paginate'    => 'nullable | numeric',
            'order'       => 'nullable | string',
            'featured'    => 'nullable | boolean',
            'has_features'    => 'nullable | array',
            'features'    => 'nullable | boolean',
            'city'    => 'nullable | numeric',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Consulta no valida, por favor revisa la documentación'
            ], 400);
        }
        $user = $request->user();
       
        $company = Company::find($user->id);

        $properties = Property::getPropertiesFromRequest($request, $company->id);

        return PropertyResource::collection($properties);
    }
}
