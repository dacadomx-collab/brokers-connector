<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {
  box-sizing: border-box;
}

.row::after {
  content: "";
  clear: both;
  display: table;
}


.col-s-1 {  float: left;
  padding: 15px;}
  .col-s-2 {  float: left;
  padding: 15px;}
  .col-s-3 {  float: left;
  padding: 15px;}
  .col-s-4 {  float: left;
  padding: 15px;}
  .col-s-5 {  float: left;
  padding: 15px;}
  .col-s-6 {  float: left;
  padding: 15px;}
  .col-s-7 {  float: left;
  padding: 15px;}
  .col-s-8 {  float: left;
  padding: 15px;}
  .col-s-9 {  float: left;
  padding: 15px;}
  .col-s-10 {  float: left;
  padding: 15px;}
  .col-s-11 {  float: left;
  padding: 15px;}
  .col-s-12 {  float: left;
  padding: 15px;}
  .col-1 {  float: left;
  padding: 15px;}
  .col-2 {  float: left;
  padding: 15px;}
  .col-3 {  float: left;
  padding: 15px;}
  .col-4 {  float: left;
  padding: 15px;}
  .col-5 {  float: left;
  padding: 15px;}
  .col-6 {  float: left;
  padding: 15px;}
  .col-7 {  float: left;
  padding: 15px;}
  .col-8 {  float: left;
  padding: 15px;}
  .col-9 {  float: left;
  padding: 15px;}
  .col-10 {  float: left;
  padding: 15px;}
  .col-11 {  float: left;
  padding: 15px;}
  .col-12 {  float: left;
  padding: 15px;}

html {
  font-family: "Lucida Sans", sans-serif;
}

.header {
  background-color: {!! $request->color !!};
  color: {!! $request->colorLetra !!};
  padding: 15px;
}

.menu ul {
  list-style-type: none;
  margin: 0;
  padding: 0;
}

.menu li {
  padding: 8px;
  margin-bottom: 7px;
  background-color: #efefef;
  color: #000000;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.menu li:hover {
  background-color: #c5cbcd;

}

.datos ul{
  list-style-type: none;
  margin: 0;
  padding: 0;
}
.datos li {
  display: inline-block;
  padding: 8px;
  margin-bottom: 7px;
  background-color: #efefef;
  color: #000000;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
  padding: 20px;

}

.info ul{
  list-style-type: none;
  margin: 0;
  padding: 0;
}
.info li{
  display: inline-block;
  padding: 8px;
  margin-bottom: 7px;
  background-color: #efefef;
  color: #000000;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
  padding: 20px;
  text-align: center;
}
.info li img{
 
  height: 30px;
  width: 30px;
}
.display{
  display: table-row;

}
.display ul{
  margin-top: 0;
  margin: 0;
  padding: 0;
}
.display li{
  list-style-type: none;
  display: inline;
}
.aside {
  background-color: #efefef;
  padding: 15px;
  color: #000000;
  text-align: center;
  font-size: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.asideiz{
  background-color: #efefef;
  padding: 15px;
  color: #000000;
  
  font-size: 14px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.letras{
  color: #999;
    font-size: 14px;
    font-weight: 400;
    text-decoration: none;
}
.footer {
  background-color: {!! $request->color !!};
  color: {!! $request->colorLetra !!};
  text-align: center;
  font-size: 15px;
  margin-bottom: 0px;
   padding: 30px 30px 30px 30px;
   margin-right: 0px;
   margin-left: 0px;
   content: "";
  clear: both;
}
.imagenli
{
  width: 35%; 
  max-height: 35%; 
  object-fit:cover; 
  height:35%;
}
.mapa{
    width: 100%; 
    max-height: 100%; 
    object-fit:cover; 
    height:100%;
  }

/* For mobile phones: */


  .col-s-1 {width: 100%;}
  .col-s-2 {width: 100%;}
  .col-s-3 {width: 100%;}
  .col-s-4 {width: 100%;}
  .col-s-5 {width: 100%;}
  .col-s-6 {width: 100%;}
  .col-s-7 {width: 100%;}
  .col-s-8 {width: 100%;}
  .col-s-9 {width: 100%;}
  .col-s-10 {width: 100%;}
  .col-s-11 {width: 100%;}
  .col-s-12 {width: 100%;}
  .col-1 {width:  100%;}
  .col-2 {width:  100%;}
  .col-3 {width:  100%;}
  .col-4 {width:  100%;}
  .col-5 {width:  100%;}
  .col-6 {width:  100%;}
  .col-7 {width:  100%;}
  .col-8 {width:  100%;}
  .col-9 {width:  100%;}
  .col-10 {width:  100%;}
  .col-11 {width:  100%;}
  .col-12 {width: 100%;}


@media only screen and (min-width: 600px) {
  /* For tablets: */
  .col-s-1 {width: 8.33%;}
  .col-s-2 {width: 16.66%;}
  .col-s-3 {width: 25%;}
  .col-s-4 {width: 33.33%;}
  .col-s-5 {width: 41.66%;}
  .col-s-6 {width: 50%;}
  .col-s-7 {width: 58.33%;}
  .col-s-8 {width: 66.66%;}
  .col-s-9 {width: 75%;}
  .col-s-10 {width: 83.33%;}
  .col-s-11 {width: 91.66%;}
  .col-s-12 {width: 100%;}

}
@media only screen and (min-width: 768px) {
  /* For desktop: */
  .col-1 {width: 8.33%;}
  .col-2 {width: 16.66%;}
  .col-3 {width: 25%;}
  .col-4 {width: 33.33%;}
  .col-5 {width: 41.66%;}
  .col-6 {width: 50%;}
  .col-7 {width: 58.33%;}
  .col-8 {width: 66.66%;}
  .col-9 {width: 75%;}
  .col-10 {width: 83.33%;}
  .col-11 {width: 91.66%;}
  .col-12 {width: 100%;}


  .imagenli
  {
    width: 35%; 
    max-height: 35%; 
    object-fit:cover; 
    height:35%;
  }
  .hr{
    border: 0.5px solid #ddd;
  }
 
}


</style>
</head>
<body style="overflow-wrap: break-word;">

<div class="header">

        <h1> <img src="@if($view != '1') {{ $url }}@endif{{ $user->company->logo}}" alt="" style="height:60px; width:auto;   border-radius:5px; background-color:white;">  {{ $user->company->name}}</h1>
  
    
</div>

<div class="row">
  <div class="col-3 col-s-12 ">
      <div class="asideiz">
          <h2 style="text-align:center;">Información de contacto</h2>
         
            <img src="@if($user->foto_avatar != '')  {{$url}}{{$user->foto_avatar}}  @endif" alt="" style="height: 100px; width:100px;margin:0 auto; display:block;">

        
          <p><strong>Email</strong> : {{ $user->email }}</p>
          <p><strong>Dirección :</strong> {{$user->company->address}}</p>
          <p><strong>Telefono: </strong>{{$user->company->phone}} </p>
      </div>
      {{--  <hr class="hr">  --}}
      <p>{{ $message}}</p>
      <hr class="hr">
  </div>

  <div class="col-6 col-s-9">
   
    <h1>{{ $property->title}}</h1>
    
    <div class="row ">
      <div class="col-s-12 col-6">
          <img style="width: 100%; max-height: 100%; object-fit:cover; height:100%;" src="@if($view != '1') {{$url}}@endif{{ $property->image }}" alt="Imagen de la propiedad">
       
      </div>
      <div class="col-s-12 col-6 display">
          <ul>
              @foreach ($images as $item)
            <li> <img style="" class="imagenli" src="@if($view != '1') {{$url}}@endif{{$item->src}}" alt=""> </li>  
              @endforeach
             
            </ul>
      </div>
      <div class="col-s-12 " align="center">
       <h2 class="precio" style="text-align:center;"> ${{ number_format($property->price,2).' '. $property->currency_attr}}</h2> 
       &nbsp;  <span>{{$property->status_name}} / {{ucfirst($property->type_prop)}}</span>
      
      </div>

      <div class="col-s-12 info">
        <ul>
          @if($property->baths_count != 0 || $property->baths_count == null)
            <!--Baños-->
            <li><img src="@if($view != '1') {{$url}}@endif/img/icon/iconproperty/ducha.png" alt=""> <br><p class="letras">{{ $property->baths_count }} Baño{{ $property->baths_count == 1 ? "" : "s"}}<p></li>    
          @endif
          @if($property->medium_baths != 0 || $property->medium_baths != null)
            <!-- Medios baños -->
            <li><img src="@if($view != '1') {{$url}}@endif/img/icon/iconproperty/inodoro.png" alt=""><br><p class="letras">{{ $property->medium_baths}} Baño{{ $property->medium_baths == 1 ? "" : "s"}} medio{{ $property->medium_baths == 1 ? "" : "s"}}</p></li>
          @endif
          @if($property->parking_lots != 0 || $property->parking_lots  != null)
            <!-- Estacionamientos -->
            <li><img src="@if($view != '1') {{$url}}@endif/img/icon/iconproperty/coche.png" alt=""><br> <p class="letras">{{ $property->parking_lots }} Estacionamiento{{ $property->parking_lots == 1 ? "" : "s"}}</p> </li>
          @endif
          @if($property->bedrooms  != 0 || $property->bedrooms  !=  null)
                                                        <!-- Dormitorios -->
                                                        <li><img src="{{ asset('img/icon/iconproperty/muebles.png') }}" alt=""><br> <p class="letras">{{$property->bedrooms}} Recamaras{{ $property->bedrooms == 1 ? "" : "s"}}</p> </li>
                                                      @endif
          @if($property->total_area  != 0 || $property->total_area  !=  null)
            <!-- Area total-->
            <li><img src="@if($view != '1') {{$url}}@endif/img/icon/iconproperty/ancho-completo.png" alt="">  <br><p class="letras">{{$property->t_area}} Área total</p></li>
          @endif
          @if($property->built_area  != 0 || $property->built_area  !=  null)
            <!-- Area construida-->
            <li><img src="@if($view != '1') {{$url}}@endif/img/icon/iconproperty/arquitectura.png" alt="">  <br><p class="letras">{{$property->b_area}} Área construida</p></li>
          @endif
          @if($property->floor  != 0 || $property->floor  !=  null)
          <!-- Area construida-->
          <li><img src="{{ asset('img/icon/iconproperty/edificio.png') }}" alt="">  <br><p class="letras">{{$property->floor}} Piso{{ $property->floor == 1 ? "" : "s"}}</p></li>
            @endif
        </ul>
      </div>
    </div>
    <div class="row">
        <div class="col-12">
              <p> <strong>Descripción de la propiedad:</strong>   {{$property->description}}</p>
        </div>
          
    </div>

    <div class="row datos">
  
        <div class="col-12 ">
          <ul>
            @if($property->floor)
              <!-- Pisos -->
              <li>Pisos:<br> {{ $property->p_floor }}</li>
            @endif
         
              <!-- Tipo de propiedad -->
              <li>Tipo de propiedad: <br> {{$property->type_prop}}</li>
           
            @if($property->length)
               <!-- Largo del terreno-->
               <li>Largo del terreno: <br> {{ $property->p_length }}</li>
            @endif
            @if($property->front)
               <!-- Frente del terreno-->
               <li>Frente del terreno: <br> {{$property->p_front }}</li>
            @endif
            @if($property->antiquity)
               <!-- Antiguedad-->
               <li>Antiguedad: <br> {{ $property->antiquity }}</li>
            @endif
          </ul>
        </div>
 
    </div>
   
</div>

  <div class="col-3 col-s-12">
    <div class="aside menu">
      <h2>Ubicación</h2>
      <p>{{$property->address}}</p>
      <img class="mapa" src="https://maps.googleapis.com/maps/api/staticmap?center={!! $property->lat !!},{!!$property->lng!!}&zoom=15&size=300x300&markers=color:red%7Clabel:P%7C{!! $property->lat !!},{!!$property->lng!!}&key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84" alt="">
      <h2>Características</h2>
      <ul style="list-style:none;">
         @foreach ($property->features as $item)
            <li class="list-group-item"> {{ ucfirst($item->name) }}
            </li>
         @endforeach
      </ul>
    </div>
  </div>
</div>

<div class="footer">
Powered by &reg; ACADEP, Derechos reservados - 2019
</div>

</body>
</html>
