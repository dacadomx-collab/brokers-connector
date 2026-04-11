<!doctype html>
<html class="no-js" prefix="og: http://ogp.me/ns#" lang="en">

<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <!-- CSRF Token -->
    <title>{{ config('app.name', 'Brokersconnector') }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:url"                 content="{{$url_full}}/property/info/{{ $property->id }}" />
    <meta property="og:type"                content="article" />
    <meta property="og:title"               content="{{$property->title}}" />
    <meta property="og:description"         content="{{$property->description}}" />
    <meta property="og:image" content="{{$url_full.$property->image}}" />
    
    <meta property="og:image:width"         content="1500" />
    <meta property="og:image:height"        content="1000" />
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@site_username">
    <meta name="twitter:title" content="{{$property->title}}">
    <meta name="twitter:description" content="{{$property->description}}">
    <meta name="twitter:image" content="{{$url_full.$property->image}}">
    <meta property="twitter:image:width"                content="1500" />
    <meta property="twitter:image:height"        content="1000" />
    <meta name="twitter:domain" content="{{$url_full}}/property/info/{{ $property->id }}">
    
    <!-- favicon
    ============================================ -->

    <link rel="shortcut icon" type="image/x-icon" href="\img\logo\img-logo-brokers2.png">
    <!-- Google Fonts
		============================================ -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">
    <!-- Bootstrap CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/bootstrap.min.css') }}">
    <!-- font awesone CSS
      ============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/font-awesome.min.css') }}">
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css?version={{ config('app.version')}}" integrity="sha256-46qynGAkLSFpVbEBog43gvNhfrOj+BmwXdxFgVK/Kvc=" crossorigin="anonymous" /> --}}
    
    <!-- animate CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/animate.css') }}">
    <!-- normalize CSS
		============================================ -->
    <!-- style CSS
		============================================ -->
    <link rel="stylesheet" href="/admin/style.css?version={{ config('app.version')}}">
    <!-- responsive CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/responsive.css') }}">

    <!-- stack styles laravel
    ============================================ -->
  
    <!-- modernizr JS
		============================================ -->
    <script src="{{ asset('admin/js/vendor/modernizr-2.8.3.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('admin/css/magnific-popup.css') }}">

    <style>
        .col-centered{
            display:inline-block !important;
            float:none !important;
            /* reset the text-align */
            text-align:left !important;
            /* inline-block space fix */
            margin-right:-4px !important;
            text-align: center !important;
        }

        @media only screen and (max-width: 767px){
            .container {
                width: 100% !important;
                margin:0 !important;
            }
        }
    </style>

    
</head>

<body>
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/es_LA/sdk.js#xfbml=1&version=v5.0&appId=379841675967074&autoLogAppEvents=1"></script>

<div class="container" style="margin-top:25px; background:white; padding:10px;">
    <div class="col-md-8">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-left">
                <h3>{{ucfirst($property->title)}}</h3>
            </div>
      
           
        </div>

        <br>
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-right">
                <div id="carousel-example-generic" class="carousel slide" data-ride="carousel" >
                    
                    {{-- <!-- Indicators -->
                    <ol class="carousel-indicators">
                        <li data-target="#carousel-example-generic" data-slide-to="0" class="active"></li>
                        <li data-target="#carousel-example-generic" data-slide-to="1"></li>
                        <li data-target="#carousel-example-generic" data-slide-to="2"></li>
                    </ol> --}}
                    
                    <!-- Wrapper for slides -->
                    <div class="carousel-inner" role="listbox" id="div-images">
                        
                        @if($property->images()->count()==0)
                            <div class="item active">
                                <a href="{{ asset('img/no-image.png') }}">
                                    <img src="{{ asset('img/no-image.png') }}" style="height:350px; width:100%; object-fit: contain;">
                                </a>
                            </div>
                        @endif

                        @foreach ($property->images() as $image)
                            
                            <div class="item {{$loop->iteration==1 ? 'active' : ''}}">
                                <a href="{{$image->src}}">
                                    <img src="{{$image->src}}" style="height:350px; width:100%; object-fit: cover;">
                                </a>
                            </div>

                        @endforeach
  
                    </div>
                    
                    <!-- Controls -->
                    <a class="left carousel-control" href="#carousel-example-generic" role="button" data-slide="prev">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                        <span class="sr-only">Previous</span>
                    </a>
                    <a class="right carousel-control" href="#carousel-example-generic" role="button" data-slide="next">
                        <span class="glyphicon glyphicon-chevron-right"></span>
                        <span class="sr-only">Next</span>
                    </a>
            </div>
            </div>
                    
        </div>
        <br><br>

        <div class="row text-center">
            @if ($property->bedrooms)
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 col-centered">
                <div class="address-hr">
                    <a href="#"><i class="fa fa-bed"></i></a>
                    <h3>{{$property->bedrooms}}</h3>
                </div>
            </div>
            @endif
            
            @if ($property->baths_count)
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 col-centered">
                <div class="address-hr">
                    <a href="#"><i class="fa fa-bath"></i></a>
                    <h3>{{$property->baths_count}}</h3>
                </div>
            </div>
            @endif
            
            @if ($property->front)
                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 col-centered">
                    <div class="address-hr">
                        <a href="#"><i class="fa fa-arrows-h" aria-hidden="true"></i></a>
                        <h3>{{$property->p_front}}</h3>
                    </div>
                </div>
            @endif

            @if ($property->length)
                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 col-centered">
                    <div class="address-hr">
                        <a href="#"><i class="fa fa-arrows-v" aria-hidden="true"></i></a>
                        <h3>{{$property->p_length}}</h3>
                    </div>
                </div>
            @endif

            @if ($property->parking_lots)
            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 col-centered">
                <div class="address-hr">
                    <a href="#"><i class="fa fa-car"></i></a>
                    <h3>{{$property->parking_lots}}</h3>
                </div>
            </div>

            @endif
        </div>
        
        <br>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
            <h2>${{number_format($property->price,2)}} {{ $property->currency_attr }}</h2> &nbsp; <span>{{$property->status_name}} / {{ucfirst($property->type_prop)}}</span>
            </div>
        </div>
        <hr>
        <div class="row" >
            <h4>Descripción</h4>
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="overflow-wrap:break-word">
            {{ $property->description }}
            </div>
        </div>
        <br>
        @if ($property->features->count() > 0)
        <div class="row">
                <h4>Características</h4>
                @foreach ($property->features as $item)
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                    <li style="list-style:none;"><i class="fa fa-check"></i> {{ ucfirst($item->name) }}
                    </li>
                </div>

                @endforeach
                
            </div>
            @endif
        <br>
        @if ($property->informationAdicional())
            <div class="row">
                <h4>Información adicional</h4>
                @if ($property->total_area)
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <div class="address-hr biography">
                            <p class=""><b>Área total</b><br />{{$property->t_area}}
                        </div>
                    </div>
                @endif

                @if ($property->built_area)
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <div class="address-hr biography">
                            <p class=""><b>Área construida</b><br />{{$property->b_area}}</p>
                        </div>
                    </div>
                @endif
                
                @if ($property->floor)
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <div class="address-hr biography" style="margin-bottom:10px;">
                            <p class=""><b>Pisos</b><br />{{$property->p_floor}}
                        </div>
                    </div>
                @endif

                @if ($property->key)
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <div class="address-hr biography" style="margin-bottom:10px;">
                            <p class=""><b> Indentificador</b><br />{{$property->p_key}}
                        </div>
                    </div>
                @endif
            </div>
        @endif
        
    </div>
<br>
    <div class="col-md-4">
        <div class="row">
            <h4>Contactar agente</h4>
            <div>
                <img src="{{$contact->foto_avatar}}" style="height:110px; width:110px; border-radius: 50%; object-fit: cover;border: 3px solid #3077b7; float:left; margin: 0 20px;">
            </div>
            <h4 style="margin-top:30px;">{{ucwords($contact->f_name)}}</h4>
            <div>{{$contact->title}}</div>
            <h5>{{$property->company->name}}</h5>
            <div class="col-md-12" style="margin-top:10px;display: inline-block;">
                <div class="col-md-12" style="font-size:16px;margin-bottom:5px;"><i class="fa fa-envelope-o" style="display:inline;" aria-hidden="true"></i> <a href="mailto:{{$contact->email}}">{{$contact->email}}</a></div>
                
                
                 
                @if($contact->phone)
                        <div class="col-md-6" style="display: inline-flex;">
                    <i class="fa fa-phone" aria-hidden="true"></i><span>&nbsp; {{$contact->phone}}</span>
                </div>
                @else
                <div class="col-md-6" style="display: inline-flex;">
                    <i class="fa fa-building"  aria-hidden="true"></i><span>&nbsp; {{$property->company->phone}}</span>
                </div>
                 @endif
                
             
              

                {{--  <div class="form-inline" style="margin-top:10px; margin-bottom:10px;">
                       

                        @if($contact->phone!="")
                       
                        @endif
                </div>  --}}
               
            </div>

            
        </div>

       <hr>
        
       @if (session()->has('success'))
       <div class="alert alert-success" role="alert">
        <strong>Bien hecho!</strong> Tu mensaje se ha enviado correctamente
        </div>
       @else
       <h4>Contáctanos</h4>
       <form action="{{ url('home/contact/form') }}" method="post">
           @csrf
           <input type="hidden" name="property_id" value="{{$property->id}}">
           <div class="form-group-inner">
               <div class="row">
                   <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                       <input type="text" name="name" placeholder="Nombre" value="" class="form-control" required/>
                   </div>
                   <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                       <input type="text" name="lastname" value="" placeholder="Apellido" class="form-control" required />
                   </div>
               </div>
           </div>
           <div class="form-group-inner">
              
                       <input type="text" name="email" value="" placeholder="Email" pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$" class="form-control"  required/>
              
           </div>
           <div class="form-group-inner">
               <textarea name="content" class="form-control" id="" placeholder="Escriba su mensaje" cols="30" rows="5">Hola me interesa esta propiedad</textarea>
           </div>
          
          

               <button class="btn btn-md btn-primary btn-block" type="submit">Enviar</button>
           

          
       
       </form>
       @endif
            
       <hr>
        {{--  <div class="row">  --}}
            <h4>Ubicación</h4>
        {{--  </div>  --}}
        <div id="map" style="width: auto; height: 300px;"></div>

         {{-- <div class="row"> 
             <h4>Más propiedades de {{$property->company->name}}</h4>
            @foreach ($property->company->properties()->limit(3)->get() as $item)
                <div class="col-md-4">
                    <a href="{{ url('property/info/'.$item->id) }}">
                        <img src="{{$item->image}}" style="height:80px; width:100%;object-fit:cover;" alt=""><br>
                        {{$item->title}}
                    
                    </a>
                </div>
            @endforeach 
         </div>  --}}
       <hr>
        {{--  <div class="row">  --}}
            <h4>Compartir</h4>
            <div class="col-xs-12">
                
                <!-- Go to www.addthis.com/dashboard to customize your tools -->
                <div class="addthis_inline_share_toolbox_j6ng"></div>
            
            </div>
           
        {{--  </div>  --}}
       
        </div>
</div>  

<!-- jquery ============================================ -->
<script src="{{ asset('admin/js/vendor/jquery-1.12.4.min.js') }}"></script>
<!-- bootstrap JS ============================================ -->
<script src="{{ asset('admin/js/bootstrap.min.js') }}"></script>
<!-- wow JS ============================================ -->
<script src="{{ asset('admin/js/wow.min.js') }}"></script>

<script src="{{ asset('admin/js/jquery.magnific-popup.min.js') }}"></script>

<script>
    $("#div-images").magnificPopup({
        delegate: 'a', // child items selector, by clicking on it popup will open
        type: 'image',
        gallery: { enabled: true }
        // other options
    });
</script>
<script>
        function initMap() {
            // The location of Uluru
            var ubication = { lat: {{ $property->lat}}, lng: {{ $property->lng }} };
            // The map, centered at Uluru
            var map = new google.maps.Map(document.getElementById('map'), { zoom: 16, center: ubication,  streetViewControl: false, });
            // The marker, positioned at Uluru
            var marker = new google.maps.Marker({ position: ubication, map: map });
            }
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84&callback=initMap" asyncdefer></script>
    
    <script src="{{ asset('admin/js/jquery.magnific-popup.min.js') }}"></script>
    <script>
        $("#div-images").magnificPopup({
            delegate: 'a', // child items selector, by clicking on it popup will open
            type: 'image',
            gallery: { enabled: true }
            // other options
        });
    </script>
   
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5d0019c521a7ad9b"></script>

</body>

</html>