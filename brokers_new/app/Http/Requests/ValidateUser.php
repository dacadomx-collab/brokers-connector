<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateUser extends FormRequest
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
            'full_name'=>'required|max:255',
            'last_name'=>'required|max:255',
         
            'title'=>'max:100',
            'phone'=>'nullable|numeric|max:9999999999999',
           
            'email'=> [ 'email',
                        'required',
                        'max:100',
                        Rule::unique('users')->ignore($this->id)->whereNull('deleted_at')],
                        
            'user_a'=>'required',

            
            
           
        ];
    }

    public function messages()
    {
        return [
            'full_name.required'=>'Ingrese el nombre o nombres',
            'full_name.max'=>'Ingrese el nombre o nombres menos de 255 caracteres',
            
            'last_name.required'=>'Ingrese los apellidos',
            'last_name.max'=>'Ingrese el apellido menos de 255 caracteres',

            'phone.numeric'=>'Ingrese solo números al teléfono',
            'phone.max'=>'Ingrese no mas de 13 dígitos al teléfono',
        
            'email.required'=>'Ingrese el correo electoníco',
            'email.max'=>'Ingrese un correo electoníco de menos de 100 caracteres',
            'email.email'=>'Ingrese un correo electronico valido',
            'email.unique'=>'El correo ya existe, favor de ingresar uno nuevo',
            
            'user_a.required'=>'Ingrese el tipo de usuario',
            
           
        ];
    }
}
