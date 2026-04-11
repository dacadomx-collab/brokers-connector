<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    public function Properties(){
        return $this->belongsToMany(Property::class, 'feature_properties');
    }

    public function getParentNameAttributte()
    {
       
        return $this->parent->name;
    }

    //Relacion de todas las caracteristicas padres
    static function parents()
    {
      
        return static::where("parent_id", null)->orWhere("parent_id", 0)->get();
    }

    //Relacion de hijos del padre
    public function children()
    {
        return $this->hasMany(Feature::class, "parent_id");
    }

    public function parent()
    {
        return $this->belongsTo(Feature::class, "parent_id");
    }
}
