<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    //
    public function invoice(){
        return $this->belongsToMany('App\invoice','invoices_services')->withPivot('price');
    }

    public function isAspiOrAmpi()
    {
        if($this->id==1 || $this->id==2)
        {
            return true;
        }

        return false;
    }

    public function isGeneral()
    {
        if($this->id==3)
        {
            return true;
        }

        return false;
    }
    
 
}
