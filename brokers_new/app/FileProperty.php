<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FileProperty;

class FileProperty extends Model
{
    protected $dates=["created_at"];
    
    public function property()
    {
        return $this->belongsTo('App\Property');
    }

    public function getFeaturedAttribute()
    {
        if($this->id==$this->property->featured_image)
        {
            return true;
        }

        return false;
    }

    public function getCountFilesPdfAttribute()
    {
       
        $count=FileProperty::where("property_id", $this->property_id)->where("type", 5)->count();
      
        return $count;
    }
    
}
