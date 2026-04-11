<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
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
            //validaciones para contactos
            'name'=>'required',
            
            'email'=>'required',//El email unico por empresa.
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Ingresar un nombre',
            'email.required' => 'Ingresar un email', 
             
        ];
    }
}
