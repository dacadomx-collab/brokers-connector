<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;
use App\Company;
use App\Property;
use App\PropertyStock;

class User extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens, Notifiable, HasRoles, SoftDeletes, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'title', 'phone','avatar', 'full_name', 'last_name', 'company_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot() {
        
        parent::boot();

        static::creating(function($user) {
            $user->token = Str::random(30);
        });
       
    }


    public function company(){
        return $this->belongsTo('App\Company');
    }

    public function contact()
    {
        return $this->hasMany('App\Contact');
    }

    public function getFotoAvatarAttribute()
    {
        if($this->avatar)
        {
            return $this->avatar;
        }

        return "/img/profile/sin-avatar.png";
    }

    //Obtener las propiedades asignadas
    public function PropertiesAsignt()
    {
        return $this->hasMany(Property::class, "agent_id");
    }

    //Confirmar correo electronico
    public function confirmEmail() {
        $this->verified = true;
        $this->token = null;
        $this->save();
    }



    public static function myProperties(){
        
        if(auth()->user()->company)
        {
            return  auth()->user()->company->properties()->published()->with('status');
          
        }

        return collect([]);

    }
    
    public static function allMyProperties(){
        
        if(auth()->user()->company)
        {
            return  auth()->user()->company->properties()->with('status');
          
        }

        return collect([]);

    }

    public static function myPropertiesOnStock(){
        
        if(auth()->user()->company)
        {

            return  auth()->user()->company->properties_on_stock;
          
        }

        return collect([]);

    }

    //Desasignar propiedades
    public function deallocate()
    {
        $properties=$this->PropertiesAsignt()->get();
        foreach($properties as $property)
        {
            $property->agent_id=null;
            $property->save();
        }
    }

    public function myCompany()
    {
        return auth()->user()->company;
    }
    public function getFNameAttribute()
    {
        if($this->full_name=="" && $this->last_name=="")
        {
            return "Nombre no ingresado";
        }
        return $this->full_name.' '. $this->last_name;
    }


public function getFNameAccentsAttribute(){

    if($this->full_name=="" && $this->last_name=="")
        {
            return "Nombre no ingresado";
        }
        $str = $this->full_name.' '. $this->last_name;

    $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
$name = strtr( $str, $unwanted_array );
return $name;
}
   
}
