<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactNote extends Model
{
    protected $dates=[
        "created_at"
    ];

   public function user(){
    return $this->belongsTo('App\User');

   }
}
