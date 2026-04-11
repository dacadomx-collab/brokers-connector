<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactPhone extends Model
{
    //Modelo de los telefonos un Contacto puede tener varios telefonos

    protected $fillable=['phone', 'type'];
  
    public function contact(){
        return $this->belongsTo('App\Contact');
    }
}
