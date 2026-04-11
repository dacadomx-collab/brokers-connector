<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactNoteRequest extends FormRequest
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
            'content' => 'required:max:1000',
            'contact_id' => 'required',
            ];
    }

    public function messages()
    {
        return [
            'content.required' => 'El contenido del mensaje no se puede enviar vacío',
            'contact.required' => 'requerido', 
             
        ];
    }
}
