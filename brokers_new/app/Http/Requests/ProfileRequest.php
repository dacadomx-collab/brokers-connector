<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'name'=>'required',
            'full_name'=>'required',
            'last_name'=>'required',
           
            'phone'=>'required|numeric|max:9999999999',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Ingresar un nombre de usuario',
            
            'full_name.required' => 'Ingresar un nombre',
            
            'last_name.required' => 'Ingresar los apellidos',
            
            'phone.required' => 'Ingresar un teléfono',
            'phone.numeric'=>'Ingresar solo números al teléfono',
            'phone.max'=>'Ingresar no mas de 10 dígitos al teléfono',
        ];
    }
}
