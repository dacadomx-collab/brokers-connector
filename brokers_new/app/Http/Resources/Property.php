<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Property extends JsonResource
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
            'id'             => $this->id,
            'title'          => $this->title,
            'status'         => $this->status->name,
            'type'         => $this->type->name,
            'featured_image' => getImageOrNull($this->image_api),
            'baths'          => getDataOrNull($this->baths),
            'bedrooms'       => getDataOrNull($this->bedrooms),
            'parking_lots'       => getDataOrNull($this->parking_lots),
            'floor'          => getDataOrNull($this->floor),
            'medium_baths'   => getDataOrNull($this->medium_baths),
            'price'          => $this->price,
            'currency'       => $this->currency_attr,
            'description' => $this->description,
            'ubication' => $this->when($request->ubication, [
                'address'  => $this->address,
                'city'  => $this->local->city->name,
                'latitud'  => (float) $this->lat,
                'longitud' => (float) $this->lng,
                ]),
            'commission' => $this->when($request->commission, [
                    'value' => $this->commission,
                    'type' => $this->commission_type,
                    ]),
            'sizes' => $this->when($request->sizes, [
                        'total'  => $this->total_area,
                        'built'  => $this->built_area,
                        'front'  => $this->front,
                        'length' => $this->length,
                        ]),
            'features' => $this->when($request->features, $this->features()->pluck('name')),
            'video' => $this->when($request->video, ($this->video ? $_SERVER['SERVER_NAME'].$this->video->src : null) ),

            'images' => $this->when($request->images, $this->imagesAPI()),
            'features' => $this->when($request->features, $this->features->pluck('name')),

        ];
    }
}
function getDataOrNull($value){
    return ($value ? $value : null);
}
function getImageOrNull($value){
    if ($value) {
      return 'http://'.$_SERVER['SERVER_NAME'].$value;
    } else {
        return null;
    }
    
}

