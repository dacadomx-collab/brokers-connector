<html lang="en">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
   
    <title>{{ config('app.name', 'Brokersconnector') }}</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta property="og:url"                 content="{{$url_full}}/property/info/{{ $property->id }}" />
    <meta property="og:type"                content="article" />
    <meta property="og:title"               content="{{$property->title}}" />
    <meta property="og:description"         content="{{$property->description}}" />
    <meta property="og:image"               content="{{$url_full.$property->image}}" />
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
    <link rel="stylesheet" href="{{ asset('admin/css/owl.carousel.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/owl.theme.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/owl.transitions.css') }}">
    <!-- animate CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/animate.css') }}">
    <!-- normalize CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/normalize.css') }}">
    <!-- meanmenu icon CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/meanmenu.min.css') }}">
    <!-- main CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/main.css') }}">
    <!-- educate icon CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/educate-custon-icon.css') }}">
    <!-- morrisjs CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/morrisjs/morris.css') }}">
    <!-- mCustomScrollbar CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/scrollbar/jquery.mCustomScrollbar.min.css') }}">
    <!-- metisMenu CSS
		============================================ -->
    <link rel="stylesheet" href="{{ asset('admin/css/metisMenu/metisMenu.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/metisMenu/metisMenu-vertical.css') }}">
    <!-- calendar CSS
		============================================ 
    <link rel="stylesheet" href="{{ asset('admin/css/calendar/fullcalendar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/calendar/fullcalendar.print.min.css') }}">-->
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

    </style>
</head>
<body>
    <div class="container" style="margin-top:25px; background:white; padding:10px; border-radius:15x;">
        <div class="col-md-7" style="background:white; padding:20px;">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-left">
                    <h3>{{ucfirst($property->title)}}</h3>
                </div>
          
               
            </div>
    
            <br>
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-right" >
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
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 col-centered">
                    <div class="address-hr">
                        <a href="#"><i class="fa fa-bed"></i></a>
                        <h3>{{$property->bedrooms}}</h3>
                    </div>
                </div>
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 col-centered">
                    <div class="address-hr">
                        <a href="#"><i class="fa fa-bath"></i></a>
                        <h3>{{$property->baths_count}}</h3>
                    </div>
                </div>
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
    
                <div class="col-lg-2 col-md-2 col-sm-2 col-xs-2 col-centered">
                    <div class="address-hr">
                        <a href="#"><i class="fa fa-car"></i></a>
                        <h3>{{$property->parking_lots}}</h3>
                    </div>
                </div>
            </div>
            
            <br>
    
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
                <h2>${{number_format($property->price,2)}} {{ $property->currency_attr }}</h2> &nbsp; <span>{{$property->status_name}} / {{ucfirst($property->type_prop)}}</span>
    
                </div>
      
            </div>
            <br>
            {{--  <div class="row" >  --}}
                <h4>Descripción</h4>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="overflow-wrap:break-word">
                {{ $property->description }}
                </div>
            {{--  </div>  --}}
            <br>
            {{--  <div class="row">  --}}
            @if (count($categories_data) > 0)
                <h4>Características</h4>
                @foreach ($categories_data as $key => $item)
                   <div class="col-md-12 pull-left" style="margin:10px;">
                       @foreach ($item as $feature)
                           <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                               @if ($loop->iteration==1)
                               <strong>{{$key}}</strong>
                               @endif
                               <li style="list-style:none;"><i class="fa fa-check"></i> {{ ucfirst($feature->name) }}
                               </li>
                           </div>
                           
                       @endforeach
                   </div>
    
                @endforeach
            @endif
            {{--  </div>  --}}
            <br>
            @if ($property->informationAdicional())
                {{--  <div class="row">  --}}
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
                {{--  </div>  --}}
            @endif
             <br>
    <br>
    <br>
    <br>
    <br>
    <br>
        </div>
   
   
        <div class="col-md-4" style="background:white; margin-right:15px; padding:20px;">
            
            <div class="row">
            <h4>Contactar agente</h4>
            <div>
                <img src="{{$contact->foto_avatar}}" style="height:110px; width:110px; border-radius: 50%; object-fit: cover;border: 3px solid #3077b7; float:left; margin: 0 20px;">
            </div>
            <h4 style="margin-top:30px;">{{ucwords($contact->f_name)}}</h4>
            <div>{{$contact->title}}</div>
            <h5>{{$contact->company->name}}</h5>
            <div class="col-md-12" style="margin-top:10px;display: inline-block;">
                <div class="col-md-12" style="font-size:16px;margin-bottom:5px;"><i class="fa fa-envelope-o" style="display:inline;" aria-hidden="true"></i> <a href="mailto:{{$contact->email}}">{{$contact->email}}</a></div>
                
                
                 
                @if($contact->phone)
                        <div class="col-md-6" style="display: inline-flex;">
                    <i class="fa fa-phone" aria-hidden="true"></i><span>&nbsp; {{$contact->phone}}</span>
                </div>
                @else
                <div class="col-md-6" style="display: inline-flex;">
                    <i class="fa fa-building"  aria-hidden="true"></i><span>&nbsp; {{$contact->company->phone}}</span>
                </div>
                 @endif
                
             
              

                {{--  <div class="form-inline" style="margin-top:10px; margin-bottom:10px;">
                       

                        @if($contact->phone!="")
                       
                        @endif
                </div>  --}}
               
            </div>

            
        </div>
            
            <br>
    
            {{--  @role("Admin")
            <div class="row text-center">
                <button type="button" id="save-property" class="btn btn-primary waves-effect waves-light">
                    Pedir compartir propiedad &nbsp; <i class="fa fa-user"></i>
                </button>
            </div>
            @endrole  --}}
    
            <br>
            <div class="row">
               <div class="col-md-12">
                <hr>
               </div>
            </div>
            {{--  <div class="row">  --}}
                <h4>Ubicación</h4>
            {{--  </div>  --}}
            <div id="map" style="width: auto; height: 300px;"></div>
            <br> <br>
            @if ($contact->company->id==$property->company->id)
                <div class="row">
                    <h4>Más propiedades de {{$property->company->name}}</h4>
                    @foreach ($property->company->properties()->bbcgeneral()->limit(3)->get() as $item)
                        <div class="col-md-4">
                            <a href="/stock/view/{{$item->id.'-'.str_slug($item->title)}}">
                                <img src="{{$item->image}}" style="height:80px; width:100%; object-fit:cover;" alt=""><br>
                                {{$item->title}}
                            
                            </a>
                        </div>
                    @endforeach
                </div>
                
            @endif
            <br> <br>
            <div class="row">
                <h4>Compartir</h4>
                <div class="col-md-4">
                    @if (Auth::user())
                    <a class="form-inline" 
                    target="_blank"  href="https://api.whatsapp.com/send?text={!!$property->title!!} {!! str_replace('https', 'http', url('/')) !!}/stock/view/{!!$property->id!!}?contact_email={!! Auth::user()->email !!}"
                    >
                    @else
                    <a class="form-inline" 
                    target="_blank"  href="https://api.whatsapp.com/send?text={!!$property->title!!} {!! str_replace('https', 'http', url('/')) !!}/stock/view/{!!$property->id!!}"
                    >
                    @endif
                    
                       <img src="{{ asset('img/social/whatsapp.png') }}" style="width:20px; height:20px;"> Whatsapp
                    </a>
               
                 </div>
            </div>
            
           
        </div>
    </div>  

<!-- jquery
		============================================ -->
        <script src="{{ asset('admin/js/vendor/jquery-1.12.4.min.js') }}"></script>
        <!-- bootstrap JS
            ============================================ -->
        <script src="{{ asset('admin/js/bootstrap.min.js') }}"></script>
        <!-- wow JS
            ============================================ -->
        <script src="{{ asset('admin/js/wow.min.js') }}"></script>
        <!-- price-slider JS
            ============================================ -->
        <script src="{{ asset('admin/js/jquery-price-slider.js') }}"></script>
        <!-- meanmenu JS
            ============================================ -->
        <script src="{{ asset('admin/js/jquery.meanmenu.js') }}"></script>
        <!-- owl.carousel JS
            ============================================ -->
        <script src="{{ asset('admin/js/owl.carousel.min.js') }}"></script>
        <!-- sticky JS
            ============================================ -->
        <script src="{{ asset('admin/js/jquery.sticky.js') }}"></script>
        <!-- scrollUp JS
            ============================================ -->
        <script src="{{ asset('admin/js/jquery.scrollUp.min.js') }}"></script>
        <!-- counterup JS
            ============================================ -->
        <script src="{{ asset('admin/js/counterup/jquery.counterup.min.js') }}"></script>
        <script src="{{ asset('admin/js/counterup/waypoints.min.js') }}"></script>
        <script src="{{ asset('admin/js/counterup/counterup-active.js') }}"></script>
        <!-- mCustomScrollbar JS
            ============================================ -->
        <script src="{{ asset('admin/js/scrollbar/jquery.mCustomScrollbar.concat.min.js') }}"></script>
        <script src="{{ asset('admin/js/scrollbar/mCustomScrollbar-active.js') }}"></script>
        <!-- metisMenu JS
            ============================================ -->
        <script src="{{ asset('admin/js/metisMenu/metisMenu.min.js') }}"></script>
        <script src="{{ asset('admin/js/metisMenu/metisMenu-active.js') }}"></script>
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
            var map = new google.maps.Map(document.getElementById('map'), { zoom: 16, center: ubication, disableDefaultUI: true });
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

    </body>
</html>

