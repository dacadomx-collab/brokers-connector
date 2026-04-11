<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\FeatureProperty;

class FeatureProperty extends Model
{
    public $timestamps = false;

    public static function syncFeatures($features, $property_id)
    {
       
        if($features != null)
        {
            foreach ($features as $feature_id) 
            {
                if(FeatureProperty::where("property_id", $property_id)->where("feature_id", $feature_id)->count() <= 0 )
                {
                    $feature = new FeatureProperty;
                    $feature->feature_id = $feature_id;
                    $feature->property_id = $property_id;
                    $feature->save();
                }
            }
        }
        else
        {
            $features=[];
        }

        FeatureProperty::where("property_id", $property_id)->whereNotIn("feature_id",$features)->delete();


    }
}
