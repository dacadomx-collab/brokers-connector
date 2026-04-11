<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    public function state(){
        return $this->belongsTo('App\State');
    }


    public function properties()
    {
        return $this->hasManyThrough('App\Property', 'App\District',
        'city_id', // Foreign key on users table...
        'local_id', // Foreign key on posts table...
        'id', // Local key on countries table...
        'id'); // Local key on users table...);
    }
}
