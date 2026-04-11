<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Contact extends Model
{


    public function comments(){

        return $this->hasMany('App\ContactNote')->orderBy('id', 'desc');
    }

    use SoftDeletes;
    //s
    protected $fillable=['name', 'phone', 'address','surname','job','email','otros'];
  
    public function company(){
        return $this->belongsTo('App\Company');
    }
    public function contactPhone(){
        return $this->hasMany('App\ContactPhone');
    }
    public function user(){
        return $this->belongsTo('App\User');
    }


    public static function checkIfExists($email, $company_id){

        return Contact::where('email', $email)->where('company_id', $company_id)->exists(); 
    }

    public function getMJobAttribute()
    {
        /*
        Mutador: m_job
        Nombre del campo: JOB - PUESTO.
        Descripción: Mutador para validar el puesto / job del contacto.
        Si no tiene un puesto retorna No ingresado, si tiene retornamos tal cual.
        Creador: Betún.
        */
        if($this->job== '' || $this->job==null)
        {
            return "No ingresado";
        }

        return $this->job;
    }
    public function getMAddressAttribute()
    {
        /*
        Mutador: m_address
        Nombre del campo: ADDRESS.
        Descripción: Mutador para validar la dirección del contacto.
        Si no tiene dirección retorna No ingresado, si tiene retornamos tal cual.
        Creador: Betún.
        */
        if($this->address== '' || $this->address==null)
        {
            return "No ingresado";
        }

        return $this->address;
    }
    public function getMOtrosAttribute(){
          /*
        Mutador: m_otros
        Nombre del campo: OTROS.
        Descripción: Mutador para validar otros datos del contacto.
        Si no tiene datos retorna No ingresado, si tiene retornamos tal cual.
        Creador: Betún.
        */
        if($this->otros== '' || $this->otros==null)
        {
            return "No ingresado";
        }

        return $this->otros;
    }
    public function getMCreatedAttribute(){
        /*
        Mutador: m_created
        Nombre del campo: created_at.
        Descripción: Mutador para validar la fecha de creación con fecha para humano.
        Si no tiene datos retorna No ingresado, si tiene retornamos la fecha para humanos.
        Creador: Betún.
        */
        if($this->created_at== '' || $this->created_at==null)
        {
            return "No ingresado";
        }
        return $this->created_at->locale('es')->diffForHumans();;
  }

  protected $dates = [
    'created_at',
    ];

}
