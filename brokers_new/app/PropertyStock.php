<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Property;

class PropertyStock extends Model
{
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function reset()
    {
        $this["24_7"]=0;
        $this["aspi"]=0;
        $this["ampi"]=0;

        $this->save();
    } 

    static function stockByName($stock="24/7")
    {
    
        switch ($stock) {
            case '24/7':
                $prop=static::select("property_id")->where("24_7",1)->get();
              
                return Property::whereIn("id", $prop);
                break;
            case 'aspi':
                $prop=static::select("property_id")->where("aspi",1)->get();
                
                return Property::whereIn("id", $prop);
                break;
            case 'ampi':
                $prop=static::select("property_id")->where("ampi",1)->get();
                
                return Property::whereIn("id", $prop);
                break;
            
            default:
                $prop=static::select("property_id")->where("24_7",1)->get();
                    
                return Property::whereIn("id", $prop);
                break;
        }
    }

}
