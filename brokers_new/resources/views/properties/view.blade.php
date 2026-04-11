@extends('layouts.app')
@section('title',$property->title)


@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span></li>
<li><a href="{{ url('properties/index') }}">Propiedades</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">{{$property->title}}</span></li>
@endsection

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('css/dropzone.min.css') }}">
<style>
  
    .mfp-zoom-out-cur {
        cursor: pointer;
    }
    .mfp-zoom-out-cur .mfp-image-holder .mfp-close {
        cursor: pointer;
    }
    .overlay{
       
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        height: 100px;
        position: absolute;
        color: white;
        font-size: 30px;
        background-color: rgb(0, 0, 0, .7);
        overflow:hidden;
        margin-left: -14px;
        
       
    }

    .btn-custon-four{
        background: white;
        border-color: #ccc;
        min-width: 41px;
    }

    .btn-custon-four:hover {
        color: #333;
        background-color: #e6e6e6;
        border-color: #adadad;
    }

    .btn {
        white-space: normal;
    }

    .div-img img {
        -webkit-transition: all .9s ease;
        /* Safari y Chrome */
        -moz-transition: all .9s ease;
        /* Firefox */
        -o-transition: all .9s ease;
        /* IE 9 */
        -ms-transition: all .9s ease;
        /* Opera */
        width: 100%;
    }

    .div-img:hover img {
        -webkit-transform: scale(1.25);
        -moz-transform: scale(1.25);
        -ms-transform: scale(1.25);
        -o-transform: scale(1.25);
        transform: scale(1.25);
    }

    .div-img {
        /*Ancho y altura son modificables al requerimiento de cada uno*/
        overflow: hidden;
    }

    .btn-custon-three{
        height: 90px;
        color: #444;font-size:18px;background-color: #fff;border-color: #3077b7;
    }
    .btn-custon-three:hover{
        color: #333;
    background-color: #e6e6e6;
    border-color: #adadad;
    }
</style>
@endpush
<div id="fb-root"></div>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/es_LA/sdk.js#xfbml=1&version=v5.0&appId=379841675967074&autoLogAppEvents=1"></script>
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                <div class="profile-info-inner">
                   
                            <div class="button-style-four btn-mg-b-10" style="text-align:right;margin-bottom:20px;">
                                    <a  href="#" data-toggle="tooltip" title="Crear Poster" class="btn btn-custon-four"><i class="fa fa-clipboard"></i></a>
                                    <a  href="{{route('ficha.create', $property->id)}}" data-toggle="tooltip" title="Crear ficha tecnica" class="btn btn-custon-four"><i class="fa fa-print" ></i></a>
                                    <a href="{{route('show.edit.properties', $property->id)}}" data-toggle="tooltip" title="Editar propiedad" class="btn btn-custon-four"><i class="fa fa-pencil" ></i></a>
                                    <a href="{{route('add.images.properties', $property->id)}}" data-toggle="tooltip" title="Imágenes y videos" class="btn btn-custon-four"><i class="fa fa-image" ></i></a>
                                   
                                    <form method="POST" action="{{route('delete.properties')}}" style="display:inline;">
                                            <input type="hidden" name="id" value="{{$property->id}}">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Esta seguro que desea eliminar esta propiedad?')" data-toggle="tooltip" title="Eliminar propiedad" class="btn btn-custon-four"><i class="fa fa-times"  style="color:#ea4335" ></i></button>
                                            
                                        </form>
                                </div>

                    <div class="profile-img">
                        <img style="max-height: 300px; object-fit:contain;" src="{{$property->image}}"
                            alt="{{$property->title}}" />
                    </div>
                    <div class="profile-details-hr">
                        <div class="row">
                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                                <div class="address-hr">
                                    <a href="#"><i class="fa fa-bed"></i></a>
                                    <h3>{{$property->bedrooms}}</h3>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                                <div class="address-hr">
                                    <a href="#"><i class="fa fa-bath"></i></a>
                                    <h3>{{$property->baths_count}}</h3>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                                <div class="address-hr">
                                    <a href="#"><i class="fa fa-car"></i></a>
                                    <h3>{{$property->parking_lots}}</h3>
                                </div>
                            </div>
                        </div>
                        <hr>


                        <div class="row">
                            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                <div class="address-hr">
                                    
                                        <div class="dropdown">
                                            <button id=""
                                                class="btn btn-custon-three btn-lg btn-block" type="button"
                                                data-toggle="dropdown">
                                                <i class="fa fa-share"></i></b><br />Compartir en redes</button>
                                                <ul class="dropdown-menu">
                                                
                                                <li>
                                                         @if(auth()->user()->company_id == 7 || auth()->user()->company_id == 17)
                                                         <a  class="form-inline" 
                                                        data-href=""
                                                        target="_blank" href="http://www.facebook.com/sharer/sharer.php?u=http://www.{{$property->company->dominio}}/property/{{$property->id}}/{{Str::slug($property->title,'-')}}?email={{auth()->user()->email}}&amp;src=sdkpreparse"
                                                        >
                                                       <img src="{{ asset('img/social/facebook.png') }}" style="width:20px; heigth:20px;"> Facebook
                                                    </a>
                                                         @else
                                                    <a  class="form-inline" 
                                                        data-href=""
                                                        target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{url('/')}}/property/info/{!!$property->id!!}&amp;src=sdkpreparse"
                                                        >
                                                       <img src="{{ asset('img/social/facebook.png') }}" style="width:20px; heigth:20px;"> Facebook
                                                    </a>
                                                         @endif
                                                </li>

                                                <li>
                                                        
                                                    <a  class="form-inline" 
                                                        target="_blank"  href="https://twitter.com/intent/tweet?url={{url('/')}}/property/info/{!!$property->id!!}"
                                                        >
                                                       <img src="{{ asset('img/social/twitter.png') }}" style="width:20px; heigth:20px;"> Twitter
                                                    </a>
                                                </li>

                                                <li>
                                                        
                                                    <a  class="form-inline"
                                                        target="_blank"  href="https://api.whatsapp.com/send?text={!!$property->title!!} {!! str_replace('https', 'http', url('/')) !!}/property/info/{!!$property->id!!}"
                                                        >
                                                       <img src="{{ asset('img/social/whatsapp.png') }}" style="width:20px; heigth:20px;"> Whatsapp
                                                    </a>
                                                </li>
                                              
                                            </ul>
                                        </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                <div class="address-hr ">
                                    <div class="dropdown">
                                        <button id="btn-asignar"
                                            class="btn btn-custon-three btn-lg btn-block" type="button"
                                            data-toggle="dropdown">
                                            <img  style="max-width:100%; height:30px;" class="img-thumbnail img-agent" src="{{ $property->image_agent }}"><br>
                                            Agente {{ $property->agent_name }}</button>
                                            <ul class="dropdown-menu">
                                            @foreach($agents as $agent)
                                            <li id="{{ $agent->id }}">
                                                <a id="{{ $agent->id }}-a" data-property="{{$property->id}}"
                                                    data-agent="{{ $agent->id }}"  data-name="{{$agent->full_name}}"  
                                                    role="button" class="btn-asignar">
                                                    @if($agent->id == $property->agent_id) <i
                                                        class="fa fa-fw fa-arrow-right"></i> @else @endif
                                                        <img style="max-width:100%; height:30px;" class="img-thumbnail" src="@if($agent->avatar != '') {{$agent->avatar}}  @else /img/profile/sin-avatar.png @endif">  
                                                        {{ auth()->user()->id==$agent->id ? 'Asignar a mi' : $agent->f_name}}
                                                </a>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                <div class="address-hr">
                                    <p style="margin-bottom: 0px;">
                                        <form action="{{ route('view.email')}}" id="form-send-email" method="get">
                                            <input type="hidden" value="{{$property->id}}" name="checkbox_property[]">
                                            
                                            <a type="submit" id="button-send-email" class="btn btn-custon-three btn-lg btn-block ">
                                            <b><i class="fa fa-envelope"></i></b><br/>Enviar por email</a>
                                        </form>
                                    </p>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                <div class="address-hr">
                                    <p>
                                        <button  type="button" class="btn btn-custon-three btn-lg btn-block"
                                            id="btn-estado">
                                            <b><i id="state-icon" class="fa fa-{{$property->published ? 'check text-success' : 'times text-danger'}}"></i></b><br />  @if($property->published) Publicada @else Despublicada  @endif </button>
                                </div>
                            </div>
                        </div>
                        {{--  <div class="row">
                                <div class="col-lg-12">
                                    <div class="address-hr">
                                        <p><b>Address</b><br /> E104, catn-2, Chandlodia Ahmedabad Gujarat, UK.</p>
                                    </div>
                                </div>
                            </div>  --}}
                    </div>
                </div>
            </div>
            <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12" style="position: relative;">
                
                {{--  <div style="float:right; position: absolute; top: 34px; right: 23px;">

                    <a class="dropdown-toggle" data-toggle="dropdown" style="cursor:pointer;">
                        Editar
                    </a>
                    <ul class="dropdown-menu pull-right"  role="menu">
                        <li><a href="{{route('show.edit.properties', $property->id)}}">
                            <i class="fa fa-pencil-square-o"  aria-hidden="true"></i> Editar propiedad</a></li>
                        <li class="divider"></li>
                        <li><a href="{{route('add.images.properties', $property->id)}}">
                            <i class="fa fa-camera" aria-hidden="true"></i>
                            Añadir fotos y video</a></li>
                             <li class="divider"></li>
                            <li>
                                <form method="POST" action="{{route('delete.properties')}}">
                                    <input type="hidden" name="id" value="{{$property->id}}">
                                    @csrf
                                    <button type="submit" style="background: none;
                                    border: 0;
                                    color: inherit;
                                    /* cursor: default; */
                                    font: inherit;
                                    line-height: normal;
                                    overflow: visible;
                                    padding: 0;
                                    -webkit-user-select: none;
                                       -moz-user-select: none;
                                        -ms-user-select: none;
                                        display: block;padding: 3px 20px;clear: both;font-weight: 400;line-height: 1.42857143;color: #333;white-space: nowrap;" class="delete-property" data-property="{{$property->id}}">
                                    <i class="fa fa-times fa-lg" style="color: #e12503"  aria-hidden="true"></i>&nbsp; Eliminar propiedad</button>
                                </form>
                                </li>
                    </ul>
                      
                  </div>    --}}
                <div class="product-payment-inner-st res-mg-t-30 analysis-progrebar-ctn">
                    <ul id="myTabedu1" class="tab-review-design">
                        <li class="active"><a href="#description">Detalles</a></li>
                        <li><a href="#reviews"> Multimedia</a></li>
                        <li><a href="#INFORMATION">Características</a></li>
                    </ul>
                    
                    <div id="myTabContent" class="tab-content custom-product-edit">
                        <div class="product-tab-list tab-pane fade active in" id="description">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="review-content-section">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                <div class="address-hr biography">
                                                    <p class="text-center">
                                                        <b>Precio</b><br />${{ number_format($property->price,2).' '. $property->currency_attr}}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                <div class="address-hr biography">
                                                    <p class="text-center"><b>Año de
                                                            construcción</b><br />{{$property->year}}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                <div class="address-hr biography">
                                                    <p class="text-center"><b>Tipo de
                                                            propiedad</b><br />{{ucfirst($property->type_prop)}}</p>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                <div class="address-hr biography">
                                                    <p class="text-center"><b>Estado</b><br />{{$property->status_name}}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row mg-b-15">
                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="skill-title">
                                                            <h2 style="margin-bottom:10px;">Descripción</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="ex-pro" style="overflow-wrap:break-word">
                                                    {{$property->description}}
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row ">
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                                <div class="skill-title">
                                                    <h2 style="margin-bottom:10px;">Ubicación</h2>
                                                </div>
                                                <p>{{$property->address}} {{$property->exterior}}, {{$property->local->name}}, {{$property->local->city->name}} {{$property->zipcode}}, 
                                                    {{$property->local->city->state->name}}, México.</p>
                                                <div class="ex-pro">
                                                    <div id="map" style="width: auto;height: 300px;"></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12" style="border-left-color: blue">
                                                <div class="skill-title">
                                                    <h2 style="margin-bottom:10px;">Información adicional</h2>
                                                </div>
                                                <div class="row">
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                        <div class="address-hr biography">
                                                            <p class="text-center"><b>Superficie total</b><br />{{$property->t_area}}
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                        <div class="address-hr biography">
                                                            <p class="text-center"><b>Área construida</b><br />{{$property->b_area}}</p>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                        <div class="address-hr biography" style="margin-bottom:10px;">
                                                            <p class="text-center"><b>Frente</b><br />{{$property->p_front}}
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                        <div class="address-hr biography" style="margin-bottom:10px;">
                                                            <p class="text-center"><b>Largo del terreno</b><br />{{$property->p_length}}
                                                        </div>
                                                    </div>
                                                
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                        <div class="address-hr biography" style="margin-bottom:10px;">
                                                            <p class="text-center"><b>Pisos</b><br />{{$property->p_floor}}
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                                                        <div class="address-hr biography" style="margin-bottom:10px;">
                                                            <p class="text-center"><b> Clave única</b><br />{{$property->p_key}}
                                                        </div>
                                                    </div>
                                                </div>
                                     
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="product-tab-list tab-pane fade" id="reviews">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="review-content-section">
                                        <div class="row mg-b-15">
                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="skill-title">
                                                           

                                                            @if($property->images()->count() != 0)
                                                            <h2>Imagenes</h2>
                                                            <div class="text-center" id="div-images">
                                                         
                                                                @foreach ($property->images() as $index =>
                                                                $image)
                                                                @if ($loop->iteration==8 && ($property->images()->count()-8) > 0)
                                                                <div class="col-md-3">
                                                                        <a href="{{$image->src}}">
                                                                    
                                                                        <div class="overlay">+{{$property->images()->count()-8}}</div>
                                                                            
                                                                        
                                                                        <img style="width: 90%;
                                                                        height: 100px; object-fit:cover;"
                                                                            src="{{$image->thumbnail}}">
                                                                    </a>
                                                                </div>
                                                                
                                                                @elseif($loop->iteration>8)
                                                                <div class="col-md-4 div-img hidden" >

                                                                        <a href="{{$image->src}}">
                                                                            <img style="width: 90%;
                                                                            height: 150px; object-fit:cover;"
                                                                                src="{{$image->thumbnail}}">
                                                                        </a>

                                                                </div>
                                                                @else
                                                                    <div class="col-md-3 " style="margin-bottom:10px;">
                                                                            <div class="div-img">
                                                                                 <a href="{{$image->src}}">
                                                                            {{-- @if ($loop->iteration==6)
                                                                                <div class="overlay">+{{$property->images()->count()-6}}</div>
                                                                            @endif --}}
                                                                            
                                                                          
                                                                            <img style="width: 90%;
                                                                            height: 100px; object-fit:cover;"
                                                                                src="{{$image->thumbnail}}">
                                                                            </a>
                                                                            </div>
                                                                           


                                                                </div>
                                                            
                                                                
                                                                @endif

                                                                @endforeach

                                                        
                                                            </div>
                                                        
                                                        @endif 
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                 
                                        @if ($property->video || $property->videosYT()->count() > 0)
                                        <div class="row mg-b-15">
                                            <div class="col-lg-12">
                                                <div class="row">
                                                    <div class="col-lg-12">
                                                        <div class="skill-title">
                                                            @if($property->images()->count() != 0)
                                                            <hr />
                                                            @endif
                                                       
                                                            <h2>Videos</h2>
                                                            @foreach ($property->videosYT() as $video)
                                                            <div class="col-lg-6">
                                                                <iframe src="{{$video->src}}" frameborder="0"
                                                                    style="width: -webkit-fill-available; height: 250px;"></iframe>
                                                            </div>
                                                            @endforeach
                                                            @if ($property->video)
                                                            <div class="col-lg-6">
                                                                <video controls style="width: 100%; height: 250px; object-fit: fill;">
                                                                    <source src="{{$property->video->src}}">
                                                                </video>
                                                            </div>
                                                            @endif

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                        
                                        @endif

                                        @if($property->images()->count() == 0 &&  ($property->videosYT()->count() == 0 || $property->video ))
                                            <hr>
                                            <h4>No tiene imágenes ni videos</h4>
                                            <a href="{{route('add.images.properties', $property->id)}}">
                                                    <i class="fa fa-camera" aria-hidden="true"></i>
                                                    Añadir fotos y videos</a>
                                        @else 
                                            @if($property->images()->count() == 0)
                                            <hr>
                                            <h4>No tienes imágenes</h4>
                                            <a href="{{route('add.images.properties', $property->id)}}">
                                                    <i class="fa fa-camera" aria-hidden="true"></i>
                                                    Añadir fotos</a>
                                            @endif
                                            @if($property->videosYT()->count() == 0 || $property->video)
                                            <hr>
                                            <h4>No tienes videos</h4>
                                            <a href="{{route('add.images.properties', $property->id)}}">
                                                    <i class="fa fa-camera" aria-hidden="true"></i>
                                                    Añadir videos</a>
                                            @endif
                                        @endif
                                        <hr>
                                        <h4>Archivos</h4>
                                        <div class="col-md-12" style="margin-bottom:10px;">
                                            <form action="{{ url('files/upload/store') }}" class="dropzone" method="POST">
                                                @csrf
                                                <input type="hidden" name="type" value="5">
                                                <input type="hidden" name="property_id" value="{{$property->id}}">
                                                <div class="form-group alert-up-pd">
                                                    <div class="dz-message needsclick download-custom">
                                                        <i class="fa fa-download edudropnone"
                                                            aria-hidden="true"></i>
                                                        <h2 class="edudropnone">
                                                            Sube un máximo de 15 archivos</h2>
                                                        {{--  <p class="edudropnone"><span class="note needsclick">(Maximo 8 MB)</span>  --}}
                                                        </p>
                                                        <input name="imageico" class="hd-pro-img" type="text" />
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        {{-- Lista de archivos --}}
                                        
                                        <h4>Lista de archivos</h4>
                                    <span id="elements_count" style="{{ $property->filesPDF()->count()<=0 ? 'display:none;' : ''}}">{{$property->filesPDF()->count() }} elemento{{$property->filesPDF()->count() > 1 ? 's' : ''}}</span>
                                        
                                        <div class="col-md-12" id="file-content">
                                            
                                                <div id="no-files" class="text-center" style="{{ $property->filesPDF()->count()>0 ? 'display:none;' : ''}}">
                                                    <img src="/images/empty.png" alt="" style ="height: 150px;  padding-bottom: 25px;">
                                                    
                                                    <h4>Aún no has subido archivos</h4>
                                                </div>

                                                <div style="{{$property->filesPDF()->count()>=10 ? 'overflow-y: scroll; height:200px;' : ''}}">
                                                    <table id="table-files" style="border-collapse: separate; border-spacing: 15px; {{ $property->filesPDF()->count()<=0 ? 'display:none;' : ''}}">
                                                        <tr>
                                                        <th class="col-md-4">Nombre</th>
                                                        <th class="col-md-2">Fecha</th>
                                                        <th class="col-md-1">Tamaño</th>
                                                        <th class="col-md-2"></th>
                                                        </tr>
                                                        <tbody id="tbody-table" >
                                                            @foreach ($property->filesPDF() as $file)
                                                            <tr class="file-pdf" id="row-{{$file->id}}">
                                                                <td class="col-md-4"> <i class="fa fa-file-pdf-o" aria-hidden="true"></i> {{basename($file->src, ".pdf")}}</td>
                                                                <td class="col-md-2">{{$file->created_at->format("d/m/Y H:s")}}</td>
                                                                <td class="col-md-1">{{ number_format(File::size(public_path($file->src))/1000,2)}} KB</td>
                                                                <td class="col-md-2">
                                                                    <a style="margin-right:5px;" href="{{$file->src}}" download="{{basename($file->src, ".pdf")}}.pdf"><i class="fa fa-download" aria-hidden="true"></i></a>
                                                                
                                                                    <a class="click-delete" data-id="{{$file->id}}" style="cursor:pointer;"><i class="fa fa-times" aria-hidden="true"></i></a>
                                                                </td>
                                                            </tr>
                                                            
                                                            @endforeach
                                                        </tbody>
                                                        
                                                    
                                                    </table>

                                                </div>

                                                
                                            
                                           
                                            
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="product-tab-list tab-pane fade" id="INFORMATION">
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <div class="review-content-section">
                                        <div class="row">
                                            {{-- <ul class="list-group list-group-flush"> --}}
                                                
                                            @foreach ($property->features as $item)
                                            <div class="col-md-4">
                                                <li class="list-group-item"><span>-</span> {{ ucfirst($item->name) }}
                                                </li>
                                            </div>

                                            @endforeach
                                            {{-- </ul> --}}

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script>
    function initMap() {
        // The location of Uluru
        var ubication = { lat: {{ $property-> lat}}, lng: {{ $property -> lng }} };
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
<script>

    $('.btn-asignar').click(function (e) {

        var property = $(this).data("property");
        var agent = $(this).data("agent");
        var name_agent = $(this).data("name");
        var img_agent = $(this+" .img-agent").attr('src');

       
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "¿Seguro desea asignar el agente a la propiedad actual?",
            buttons: {
            yes: {
                class: 'lobibox-btn lobibox-btn-yes',
                text: 'Confirmar',
                closeOnClick: true
            },
            no: {
                class: 'lobibox-btn lobibox-btn-no',
                text: 'Cancelar',
                closeOnClick: true
            }
        },
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                    $.ajax(
                        {
                            type: "post",
                            url: " /properties/agent ",
                            data: { property_id: property, agent_id: agent, "_token": "{{ csrf_token()  }}" },
                            success: function (result) {
                                //btn.parents('li').hide();
                                Lobibox.notify('success', {
                                    title: 'Exito',
                                    position: 'top right',
                                    showClass: 'fadeInDown',
                                    hideClass: 'fadeUpDown',
                                    msg: "Agente " + name_agent + " asignado"
                                });
                                //$("#agente").html(a);img-agent
                                var name = $("#" + result.ant + "-a").text();
                                var imagen =" <img  style='max-width:100%; height:30px;' class='img-thumbnail img-agent' src=''><br>";
                                //Agregar flecha y el nombre del agente asignado
                                $("#" + agent + "-a").html("<i class='fa fa-fw fa-arrow-right'> </i> <img  style='max-width:100%; height:30px;' class='img-thumbnail img-agent' src='"+result.agent_img+"'> " + name_agent);
                                //Actulizar el nombre del boton
                                $("#btn-asignar").html( '<img  style="max-width:100%; height:30px;" class="img-thumbnail img-agent" src="'+result.agent_img+'"><br>'+ name_agent);
                                $("#" + result.ant + "-a").html('<img  style="max-width:100%; height:30px;" class="img-thumbnail img-agent" src="'+img_agent+'">'+name);
                                //$("#" + result.ant).show();
                            },
                            error: function (result) {
                                alert("error");
                            }
                        });
                }
            }
        });
    });
</script>
<script>
    var btn = $("#btn-estado");
    var property = {{ $property-> id }}
    var published = {{ $property-> published }}


    btn.click(function (e) {
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: true,
            msg: "¿Cambiar estado de la propiedad?",
            buttons: {
                yes: {
                    class: 'btn btn-primary waves-effect waves-light',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                },
        },
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                    $.ajax(
                        {
                            type: "post",
                            url: "/properties/state",
                            data: { published: published, property_id: property, "_token": "{{ csrf_token()  }}" },
                            success: function (result) {

                                //alert("estado actual " + result.published);
                                var estatus = "";
                                if (result.published == 1) {
                                    //
                                    $("#state").css("color", "#34a854");
                                  
                                    $("#state").attr('data-original-title', 'Publicado');
                                    
                                    $("#btn-estado").html(' <b><i id="state-icon" class="fa fa-check text-success"></i></b><br /> Publicada');
                                    //$("#state").title('Publicado');
                                    estatus = "<i class='fa fa-check' ></i> El estado de la propiedad cambio a publicado";
                                } else {
                                    $("#state").css("color", "#ea4331");
                                
                                    $("#btn-estado").html('<b><i id="state-icon" class="fa fa-times text-danger"></i></b><br />Despublicada');
                                    
                                   
                                    $("#state").attr('data-original-title', 'No publicado');
                                    estatus = "<i class='fa fa-times' ></i> El estado de la propiedad cambio a no publicado";
                                }
                                Lobibox.notify('success', {
                                    title: 'Exito',
                                    position: 'top right',
                                    showClass: 'fadeInDown',
                                    hideClass: 'fadeUpDown',
                                    msg: estatus
                                });


                                published = result.published;

                                //'#34a854' : '#ea4331' 
                            },
                            error: function (result) {
                                alert("error");
                            }
                        });
                }
            }
        });
    });
</script>
<script>
     $(".dropzone").dropzone({ 
     acceptedFiles: ".pdf",
   
    
     dictCancelUpload: "Cancelar subida",
     dictRemoveFile: "Eliminar archivo",
     init: function(){
        var th = this;
        this.on('queuecomplete', function(){
          
           
                th.removeAllFiles();
           
        })
    },
     
     success: function(file, responseText) {
         file['id']=null;
         
         switch (responseText.type_msg) {
             case 1:
                 Lobibox.notify('error', {
                 title: 'Archivo no subido',
                 position: 'top right',
                 showClass: 'fadeInDown',
                 hideClass: 'fadeUpDown',
                 msg: responseText.msg
                 });
                 break;
             case 2:
                   
                 file['id']=responseText.array["id"];
                 var number_files = $(".file-pdf").length;
                 
                if(number_files <= 0)
                {
                    
                    $("#no-files").css('display', 'none');
                    $("#table-files").css('display', 'block');
                    $("#elements_count").css('display', 'block');
                    
                }
                
                 $("#tbody-table").append('<tr id="row-'+responseText.array["id"]+'">'+
                    '<td class="col-md-4"> <i class="fa fa-file-pdf-o" aria-hidden="true"></i> '+responseText.array["name"]+'</td>'+
                    '<td class="col-md-2">'+responseText.array["date_format"]+'</td>'+
                    '<td class="col-md-1">'+responseText.array["size"]+' KB</td>'+
                    '<td class="col-md-2">'+
                    '<a style="margin-right:7px;" href="'+responseText.array["src"]+'" download="'+responseText.array["name"]+'.pdf"><i class="fa fa-download" aria-hidden="true"></i></a>'+
                    '<a class="click-delete" data-id="'+responseText.array["id"]+'" style="cursor:pointer;"><i class="fa fa-times" aria-hidden="true"></i></a>'+
                    '</td>'+
                    '</tr>')
                    if(responseText.array["count"] > 1)
                    {
                        $("#elements_count").html(responseText.array["count"]+" elementos");
                    }
                    else
                    {
                        $("#elements_count").html(responseText.array["count"]+" elemento");
                    }
                    
                 break;
         
             default:
                 break;
         }
              
     },
     error: function(file,res){
         
         $(file.previewElement).addClass("dz-error").find('.dz-error-message').text(res.msg);
         Lobibox.notify('error', {
             title: 'Archivo no subido',
             position: 'top right',
             showClass: 'fadeInDown',
             hideClass: 'fadeUpDown',
             msg: res.msg
         });
     },
    
     queuecomplete: function(file){
        
        // //  debugger;
        // setTimeout(() => {
            
        //      location.reload();
        // }, 800);
     },
    //  complete: function(file)
    //  {
       
    //     file.previewElement.remove();
    //     $('.dz-message').show();
    //  }
     
     
 });

    function files_count(count)
    {
        if(count <= 0)
        {
            //si no tiene nada retorna falso.
            $("#no-files").css('display', 'block');
            $("#table-files").css('display', 'none');
            $("#elements_count").css('display', 'none');
            //alert("se muestra " + number_videos);
        }
        else{
            if(count>1)
            {
                $("#elements_count").html(count+" elementos");
            }
            else
            {
                $("#elements_count").html(count+" elemento");
            }
            
            $("#no-files").css('display', 'none');
            $("#table-files").css('display', 'block');
                //alert("no se muestra " + number_videos);
        }
    }
    $("#tbody-table").on("click",".click-delete", function(){

        var id=$(this).data("id");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: true,
            msg: "¿Desea eliminar este archivo?",
            buttons: {
                yes: {
                    class: 'btn btn-primary waves-effect waves-light',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                },
            },
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                        $.ajaxSetup({
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            }
                        });

                        $.ajax({
                            url: '/files/upload/delete',
                            data: {id:id},     
                            type: 'POST',  
                            success: function (res) {
                                
                                Lobibox.notify('success', {
                                    title: 'Archivo eliminado',
                                    position: 'top right',
                                    showClass: 'fadeInDown',
                                    hideClass: 'fadeUpDown',
                                    msg: res.msg
                                });
                                $("#row-"+id).fadeOut(function(){
                                    $("#row-"+id).remove();
                                    files_count(res.count)
                                })
                                
                               
                                
                            
                            },      
                            error: function (res) {
                                Lobibox.notify('error', {
                                    title: 'Algo salio mal',
                                    position: 'top right',
                                    showClass: 'fadeInDown',
                                    hideClass: 'fadeUpDown',
                                    msg: 'Ocurrio un error mientras se eliminaba el archivo, por favor contacte a soporte'
                                });
                                
                            },      
                        });
                }
            }
        });
    });


  
    $("#button-send-email").click(function (e) {
      $("#form-send-email").submit();
    });
</script>
@if (Session::has('success'))
<script>
    Lobibox.notify('success', {
        title: 'Aviso!',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "{{ session('success') }}"
    });
</script>
@endif
@if (Session::has('error'))
<script>
    Lobibox.notify('error', {
        title: 'Error',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "{{ session('error') }}"
    });
  
</script>
@endif
@endpush