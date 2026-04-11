<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
           'title' => 'required|max:100',
           'prop_type_id' => 'required',
           'prop_status_id' => 'required',
           'key'=>"max:15",
           'description'=>"required|max:1000",
           'address'=>"max:150",
           'price' => 'required|numeric|min:0|max:9999999999',
           'currency' => 'required',
           'bedroom' => 'max:999',
           'baths' => 'max:999',
           'commission' => 'max:9999999999',
           'medium_baths' => 'max:999',
           'parking_lots' => 'max:999',

            'floor' => "max:99",
            'built_area' => "max:9999999999",
            'total_area' =>"max:9999999999", 
            'front' =>"max:9999999999",
            'lengt' =>"max:9999999999", 
           
           
           'local_id' => 'required',
           'mun' => 'required',
           'state' => 'required',
           'lat' => 'required',
           'lng' => 'required',
          
          
            
        ];
    }

    public function messages()
    {
        return[
            'local_id.required' => 'Ingresar la localidad',
            'mun.required' => 'Ingresar el municipio',
            'state.required' => 'Ingresar el estado',

            'key.max'=>"Ingresar un identificador maximo de 15 caracteres",
            'title.max'=>"Ingresar en titulo un maximo de 100 caracteres",
            'description.max'=>"Ingresar en descripción un maximo de 1000 caracteres",

            'price.max' => "Ingresar en precio un numero menor que 9999999999",

            'bedroom.max' => 'Ingresar en habitaciones un numero menor que 999',
            'baths.max' => 'Ingresar en baños un numero menor que 999',
            'commission.max' => 'Ingresar en comisión un numero menor que 9999999999',
            'medium_baths.max' => 'Ingresar en medios baños un numero menor que 999',
            'parking_lots.max' => 'Ingresar en estacionamientos un numero menor que 999',
            
            'floor.max' => "Ingresar en pisos un numero menor que 99",
            'built_area.max' => "Ingresar en area contruida un numero menor que 9999999999",
            'total_area.max' =>"Ingresar en area total un numero menor que 9999999999", 
            'front.max' =>"Ingresar en frente un numero menor que 9999999999",
            'lengt.max' =>"Ingresar en largo del terreno un numero menor que 9999999999", 

           
        ];
    }
}
