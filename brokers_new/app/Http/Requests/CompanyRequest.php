<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
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
            'phone'=>'required',
            'rfc'=>'required',
            'colony'=>'required',
            'zipcode'=>'required',
            'email'=>[  'required',
                        'email',
                        Rule::unique('companies')->ignore($this->id)],
            'address'=>'required',
            'about'=>'max:1500',
           
           
            
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Ingresar un nombre',
            'phone.required' => 'Ingresar un teléfono',

            'email.required' => 'Ingresar el correo eléctronico',
            'email.email' => 'Ingresar un correo valido',
            'email.unique' => 'El correo ya esta en uso, favor de ingresar uno nuevo',

            'address.required' => 'Ingresar una dirección',
            'rfc.required' => 'Ingresar un RFC',
            'colony.required' => 'Ingresar una colonia',
            'zipcode.required' => 'Ingresar el codigo postal',
            'about.max' => 'No se permiten mas de 1500 caracteres',
        ];
    }
}
