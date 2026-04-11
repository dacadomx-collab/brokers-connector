<?php

namespace App;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Service;
class Company extends Model
{
   protected $fillable=['name', 'phone', 'logo', 'dominio' ,'email', 'address','rfc','colony','zipcode', 'plan', 'active','owner', 'about','package','cutoff_date'];

   //consigue todas las propiedades que le pertenecesn a la compañia
   public function properties()
   {
      return $this->hasMany('App\Property');
   }


  public function users()
  {
      return $this->hasMany('App\User');
  }

  public function service(){
     return $this->belongsTo(Service::class, "package");
  }

  public function getOwnerUserAttribute()
  {
      return $this->hasMany('App\User')->Role('Admin')->first();
  }

   public function onStock()
   {
      return $this->hasManyThrough('App\PropertyStock', 'App\Property');
   }


   public function contacts()
   {
      return $this->hasMany('App\Contact');
   }
   public function invoices()
   {
      return $this->hasMany('App\Invoice');
   }

   public function getOwnerAttribute()
   {
     return $this->owner->f_name;
   }

   public function getLogoImageAttribute()
   {
     return $this->logo ? $this->logo : '/img/no-image.png';
   }
   public function getBannerImageAttribute()
   {
     return $this->banner ? $this->banner : '/img/no-image.png';
   }
   public function getTeamImageAttribute()
   {
     return $this->team ? $this->team : '/img/no-image.png';
   }
   public function getCoverImageAttribute()
   {
     return $this->cover ? $this->cover : '/img/no-image.png';
   }
   public function getMSubtotalAttribute()
   {
      $package = $this->m_package;
      $subtotal = $this->m_total_users + $this->m_package->price;
      return $subtotal;
   }
   /*
   public function getMTotalUsersAAttribute()
   {
      $users = $this->users->where('active','1')->count();
      $package = $this->m_package;
      if($package->users_included >= $users)
      {
         $users = $users - $package->users_included;
      }

      $price_for_user = $package->user_price;
      return $price_for_user * $users;
   }
   */
   public function getMTotalUsersAttribute()
   {
      $package = $this->m_package;
      $users = $this->users;
      $count_users = 0;
      foreach($users as $user)
      {
          if(!$user->active)
          {
              //Si el usuario esta desactivado no se cuenta.
              continue;
          }
          if($this->m_package == '1')//si el paquete es single no hacer nada (SE PUEDE CAMBIAR A si EL PaqueTE no tiene usuarios incluidos no hacer nada)
          {
             $count_users = 0;
              continue;
          }
          //$this->invoices->services()->attach(4,['price'=>$package->user_price]);//Guardamos en la tabla pivote
          $count_users++;
      }

      if($count_users >= $package->users_included)
      {
         $count_users = $count_users - $package->users_included;

      }
      else {
         $count_users = 0;
      }
      $price_for_user = $package->user_price;

      return $count_users * $price_for_user ;
   }
   public function getMPackageAttribute()
   {
      $package = $this->package;
      $service_package = Service::find($package);
      return $service_package;
   }

   //Obtener propiedades de la bolsa por compañia
   public function getPropertiesOnStockAttribute()
   {
      return $this->properties()->published()->where(function ($query) {
         $query->where("bbc_general", '=', 1)
               ->orWhere('bbc_aspi', '=', 1)
               ->orWhere('bbc_ampi', '=', 1);
      });

   }

   // atributo que retorna si una compañia esta activa o no
   public function getIsActiveAttribute()
   {
      //obtenemos el ultimo recibo
      $last_invoice = $this->invoices()
      ->where("status", 1)->latest()->first();


      //si la fecha de expiracion del ultimo recibo es mayor, retorna true (significa que esta vigemte), caso contrario retorna false
      return $last_invoice->due_date->greaterThan(Carbon::now());

   }


   public function getHasToPayAttribute()
   {
      //obtenemos el ultimo recibo
      $last_invoice = $this->invoices()
      ->where("status", 1)->latest()->first();



      return $last_invoice->due_date->lessThan(Carbon::now()->addDays(5));


    //  17 < 17



   }






}
