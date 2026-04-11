<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PropertyStatus extends Model
{
    public function scopeNotInclude($query)
    {
        if(auth()->user()->company->id!=16)
        {
            return  $query->where('luly', false);
        }

        return $query;
        
    }

    public function scopeLuly($query)
    {
        return $query->where('luly', false);
    }
}
