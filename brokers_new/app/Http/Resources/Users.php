<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Users extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
    
        return [
            'name' => $this->f_name,
            'email' => $this->email,
            'phone' =>$this->phone,
            'image'=> config('app.server').$this->avatar,
            'rol' => $this->title,
        ];
    }
}
