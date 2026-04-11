<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Town extends Model
{
    protected $table="ciudades";

    public function state()
    {
      return  $this->belongsTo(State::class, "parent_id");
    }
}
