<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\ActiveScope;
use Illuminate\Support\Collection;
// use App\Feature;

class Property extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
       'title', 'title_en','description', 'description_en','bedrooms', 'baths', 'medium_baths', 'built_area', 'total_area', 'price', 'currency', 'address',
       'lng', 'lat', 'commission', 'type_commission', 'featured_image', 'antiquity', 'video', 'published', 'company_id', 'prop_status_id',
       'prop_use_id','prop_type_id', 'agent_id', 'created_by', 'parking_lots', 'floor', 'length', 'front', 'key', 'local_id' ,"bbc_general", 
       "bbc_aspi", "bbc_ampi" ,'zipcode', 'exterior', 'interior'
    
    ];

    protected $dates=[
        "created_at"
    ];
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('published', function ($query) {
            return $query;
        });

    }

    
    
    public function status(){
        return $this->belongsTo('App\PropertyStatus', 'prop_status_id');
    }
    public function use(){
        return $this->belongsTo('App\PropertyUse', 'prop_use_id');
    }
    
    public function company(){
        return $this->belongsTo('App\Company');
    }

    public function stockshared(){
        return $this->hasOne(PropertyStock::class);
    }

    public function type(){
        return $this->belongsTo('App\PropertyType', 'prop_type_id');
    }


    public function agent(){
        return $this->belongsTo('App\User', 'agent_id');
    }

    public function featured_img(){
        return $this->belongsTo('App\FileProperty','featured_image');
    }

    public function local(){
        return $this->belongsTo( District::class, 'local_id' );
    }
  
    public function fileProperties(){
        return $this->hasMany('App\FileProperty');
    }

    public function features()
    {
        return $this->belongsToMany('App\Feature', 'feature_properties');
    }

    // public function featureProperties(){
    //     return $this->hasMany('App\FeatureProperty');
    // }


    public function scopePublished($query)
    {
        return $query->where('published', true);
    }


    public function getAgentAssigntAttribute()
    {
        if($this->agent)
        {
            return $this->agent;
        }

        return $this->company->owner_user;
    }

    public function getCurrencyAttrAttribute()
    {
        if($this->currency)
        {
            $currency = config('app.currency');
            return $currency[$this->currency];
        }
        return "";
    }

    public function getTAreaAttribute()
    {
        //Área total
        if($this->total_area==0 || $this->total_area==null)
        {
            return "No ingresado";
        }

        return number_format($this->total_area).' m²';
    }

    public function getBAreaAttribute()
    {
        //Area construida
        if($this->built_area==0 || $this->built_area==null)
        {
            return "No ingresado";
        }

        return number_format($this->built_area).' m²';
    }
  
    //de aqui
    public function getPKeyAttribute()
    {
        if($this->key=='' || $this->key==null)
        {
           return "No ingresado";
        }
        return $this->key;
    }
    public function getPFloorAttribute()
    {
        if($this->floor=='' || $this->floor==null)
        {
            return "No ingresado";
        }
        return $this->floor;
    }
    public function getPFrontAttribute()
    {
        if($this->front=='' || $this->front==null)
        {
            return "No ingresado";
        }
        return  number_format($this->front).' m';
    }
    public function getPlengthAttribute()
    {
        if($this->length=='' || $this->length==null)
        {
            return "No ingresado";
        }
        return  number_format($this->length).' m';
    }
 
    //aqui
    
    public function getTypePropAttribute()
    {
        return ucfirst($this->type->name);
    }

    public function getStatusNameAttribute()
    {
        return $this->status->name;
    }

    public function getAgentNameAttribute()
    {
        if($this->agent)
        {
            return $this->agent->full_name;
        }
        return "No Asignado";
    }

    public function getYearAttribute()
    {
        if($this->antiquity)
        {
            return $this->antiquity;
        }
        return "Sin fecha";
    }

    public function getBathsCountAttribute()
    {
        
        return $this->baths+($this->medium_baths/2);
    }

    public function images(){

        return $this->fileProperties()->where('type', 1)->orderBy('index_order', 'asc')->get();
    }

    public function videosYT(){
        return $this->fileProperties()->where('type', 3)->get();
    }

    public function filesPDF(){
        return $this->fileProperties()->where('type', 5)->get();
    }

    //Scopes para obtener las propieades en la bolsa
    //Bolsa general 
    public function scopeBbcGeneral($query)
    {
        return $query->where("bbc_general",1 )->published();
    }

    //Bolsa ampi
    public function scopeBbcAmpi($query)
    {
        return $query->where("bbc_ampi",1 )->published();
    }

    //Bolsa aspi 
    public function scopeBbcAspi($query)
    {
        return $query->where("bbc_aspi",1 )->published();
    }

    //En bolsa

    public function getFullAddressAttribute()
    {
        $address=$this->address;
        if($this->exterior)
        {
            $address.=" ,Exterior: #".$this->exterior;
        }
        if($this->interior)
        {
            $address.=" ,Interior: #".$this->interior;
        }
        if($this->zipcode)
        {
            $address.=" ,CP: ".$this->zipcoode;
        }
        
        return $address;
    }

    public function getVideoAttribute()
    {
        $video_src=$this->fileProperties()->where('type', 4)->first();
        if(!$video_src)
        { return null; }

        return $video_src;
    }

    public function getImageAttribute()
    {
        if ($this->featured_image) 
        {
           
            $image = $this->featured_img()->first();
           
            
            if($image)
            {
                return $image->src;
            }
            
            return '/img/no-image.png';
            
        }
        
        
        return '/img/no-image.png';
        

        
    }
    public function getImageApiAttribute()
    {
        if ($this->featured_image) 
        {
           
            $image = $this->fileProperties()->where("id", $this->featured_image)->first();
            
            if($image)
            {
                return $image->thumbnail;
            }
            
            return '/img/no-image.png';
            
        }
        
        
        return '/img/no-image.png';
    }

    public function getImageAgentAttribute()
    {
        if($this->agent == null)
        {
            return '/img/profile/sin-avatar.png';
        }
        if ($this->agent->avatar != '') 
        {
            return $this->agent->avatar;
        }
        return '/img/profile/sin-avatar.png';
        
    }

    public function getCommissionTypeAttribute(){
        if($this->type_commission)
        {
            return config('app.commission')[$this->type_commission];
        }

        return 0;
    }

    //portales
    public function getCasafyStatusAttribute(){

        if ($this->prop_type_id == 18 && $this->prop_status_id == 1 ) {
            $value = 'for rent local';
        }elseif($this->prop_type_id == 18 && $this->prop_status_id == 2){
            $value = 'for sale local';
        }elseif($this->prop_type_id == 28 && $this->prop_status_id == 2){
            $value = 'land for sale';
        }elseif($this->prop_type_id == 2 && $this->prop_status_id == 1){
            $value = 'office for rent';
        }elseif($this->prop_type_id == 2 && $this->prop_status_id == 2){
            $value = 'office for sale';
        }elseif($this->prop_type_id == 20 && $this->prop_status_id == 1){
            $value = 'warehouse for rent';
        }elseif($this->prop_type_id == 20 && $this->prop_status_id == 2){
            $value = 'warehouse for sale';
        }
        else{
            switch ($this->prop_status_id) {
                case 1:
                   $value = 'for rent';
                    break;
                case 2:
                    $value = 'for sale';
                    break;
                case 8:
                    $value = 'for sale';
                    break;
                
                default:
                $value = '';
                    break;
            }
        }
        
        return $value;
    }

    public function getCasafyTypeAttribute(){
        switch ($this->prop_type_id) {
            case 1: //departamento
               $value = 'apartment';
                break;
            case 2: //oficina
                $value = 'office';
                break;
            case 4: //condominio
                $value = 'other';
                break;
            case 9: //bodega almacen
                $value = 'garage';
                break;
            case 11: //duplex
                $value = 'house';
                break;
            case 18: //local
                $value = 'other';
                break;
            case 20: //nave
                $value = 'other';
                break;
            case 23: //rancho
                $value = 'farm';
                break;
            case 27: //casa
                $value = 'house';
                break;
            case 28: //terreno
                $value = 'lot';
                break;
            case 29: //recamara
                $value = 'share apartment';
                break;
            case 30: //edificio
                $value = 'building';
                break;
            
            default:
            $value = 'other';
                break;
        }
        
        return $value;
    }


    static public function getPropertiesFromRequest($request, $id_company){

        //$company = Company::where('api_key', $request->api_key)->first();
        $company = Company::find($id_company);


        if($request->city){

            $properties = City::find($request->city)->properties()->where('company_id', $company->id);
           
        }else{

            $properties = $company->properties()->with('local.city');
        }
                
        if($request->featured){
            $properties->where('featured', $request->featured);
        }

        if ($request->free_search) {
            $properties->where("title", "LIKE", "%".$request->free_search."%");
        }

        if ($request->has_features) {
            if(count($request->has_features)){
                $features = collect($request->has_features)->pluck('key');
                // dd($features);
                foreach ($features as $feature) {
                    $properties->whereHas('features', function($q) use ($feature) {
                        $q->where('id', $feature);
                    });
                }
                // dd($properties->get());
            }
        }

        if($request->pricemin){
            $properties->where('price','>=', $request->pricemin);
        }
        if($request->pricemax){
            $properties->where('price','<=', $request->pricemax);
        }
        
        if($request->type){
            $properties->where('prop_type_id', $request->type);
        }
        
        if($request->status){
            $properties->where('prop_status_id', $request->status);
        }
        if($request->baths){
            $properties->where('baths', $request->baths);
        }
        if($request->bedrooms){
            $properties->where('bedrooms', $request->bedrooms);
        }

        if($request->parking_lots){
            $properties->where('parking_lots', $request->parking_lots);
        }

        if($request->order){
            $properties->orderBy('created_at', $request->order);
        }
        
        if ($request->paginate) 
        {
            return $properties->paginate($request->paginate)->appends($request->except('page'));
        } 
        else 
        {
            if($request->limit)
            {
                $properties->limit($request->limit);
            }
            
            return $properties->get();
        }
            
    }

    public function imagesAPI(){

        $images = array();

        foreach ($this->images()->pluck('src') as $image) {
            //if ($this->featured_image != $image) {

                array_push($images, config('app.server').$image);
           // }
        }
        
        return $images;
    }
    
       public function imagesPDF(){

        $images = array();

        foreach ($this->images()->pluck('src') as $image) {
            //if ($this->featured_image != $image) {

                array_push($images, $image);
           // }
        }
        
        return $images;
    }
    
    //Funcion para desactivar opciones de bolsas no elegidas
    public function offBbcOptions($request)
    {
        if(!$request->bbc_general)
        {
            if($this->bbc_general)
            {
                $this->bbc_general=0;
            }
        }
        if(!$request->bbc_aspi)
        {
            if($this->bbc_aspi)
            {
                $this->bbc_aspi=0;
            }
        }
        if(!$request->bbc_ampi)
        {
            if($this->bbc_ampi)
            {
                $this->bbc_ampi=0;
            }
        }

        $this->save();
    }

    //Funcion para regresar el tipo de propieadad para el portal lamudi
    public function getLamudiTypeAttribute()
    {
        switch( $this->type->id)
        {
            case 1:
            case 4:
                return "Departamento";
            break;

            case 2:
                return "Oficina";
            break;

            case 9:
            case 18:
            case 20:
            case 30:
                return "Comercio";
            break;

            case 11:
            case 27:
            case 29:
                return "Casa";
            break;

            case 23:
            case 28:
                return "Terreno";
            break;

            default:
                return "Comercio";
            break;


        }
    }

    //Funcion para regresar estado de propieadad para el portal lamudi
    public function getLamudiStatusAttribute()
    {
        switch($this->status->id)
        {
            case 1:
                return "Renta";
            break;

            case 2:
            case 8:
                return "Venta";
            break;

            default:
                return "Venta";
            break;

        }
    }

    //Verificar si existe informacion adicional
    public function informationAdicional()
    {
        if(($this->total_area) || ($this->built_area) || ($this->floor) || ($this->key))
        {
            return true;
        }

        return false;
    }

    //verificar si la propiedad tiene una caracteristica
    public function hasFeature($feature_id){
       if ($this->features()->where('id', $feature_id)->first()) {
           return 1;
       } else {
          return 0;
       }
       
    }

}