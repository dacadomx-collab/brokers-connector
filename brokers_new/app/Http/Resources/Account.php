<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Account extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            
            'logo'       => $this->when($request->logo, getDataOrNull($this->logo)),
            'banner'     => $this->when($request->banner, getDataOrNull($this->banner)),
            'cover'      => $this->when($request->cover, getDataOrNull($this->cover)),
            'team'       => $this->when($request->team, getDataOrNull($this->team)),
            'about' => $this->when($request->about, $this->about),
            'address'    => $this->when($request->address, $this->address),
            'phone'      => $this->when($request->phone, $this->phone),
            'email'      => $this->when($request->email, $this->email),
            'name'      => $this->when($request->name, $this->name),
        ];
    }
}

function getDataOrNull($data){

    return $data ? config('app.server').$data : null;

}
