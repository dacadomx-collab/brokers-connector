@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.css?version={{ config('app.version')}}" integrity="sha256-P8k8w/LewmGk29Zwz89HahX3Wda5Bm8wu2XkCC0DL9s=" crossorigin="anonymous" />

<style>
    .holds-the-iframe {
        background:url(/images/loading.gif) center center no-repeat;
      }
      .square{
          width: 150px;
          height: 100px;
          padding: 20px 30px 20px 5px;
         
      }
</style>
@endpush

@section('title','Crear ficha técnica')


@section('breadcome')
    <li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
    <li><a href="{{route('view.property', $property->id)}}">{{$property->title}}</a> <span class="bread-slash">/</span></li>
    <li><span class="bread-blod">Crear ficha técnica</span></li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <form action="{{ url('home/property/ficha/preview') }}" method="POST" target="_blank">
            @csrf
            <input type="hidden" name="id_property" value="{{$property->id}}">
        <div class="row">
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <div class="courses-area">
                        <div class="container-fluid analysis-progrebar-ctn">
                            <div class="basic-login-inner">
                                <div class="row">

                                
                                <h3>Personaliza tu ficha</h3>
                                <p>Edita los titulos y elige tu estilo</p>
                                   
                                    <div class="form-group-inner">
                                        <div> <label>Título</label> 
                                            <span style="float: right;">
                                                <label for="notitle">No mostrar</label>
                                                <input class="input-disabled" id="notitle" class="form-check-input" type="checkbox" name="title" value="0">
                                            </span>

                                        </div>
                                        <input type="text" name="title" class="form-control" value="{{$property->title}}" placeholder="Personaliza el titulo" />
                                    </div>
                                    <div class="form-group-inner">
                                        <div> <label>Descripción</label> 
                                            <span style="float: right;">
                                                <label for="nodescription">No mostrar</label>
                                                <input class="input-disabled" id="nodescription" class="form-check-input" type="checkbox" name="description" value="0">
                                            </span>

                                        </div>
                                        <textarea name="description" class="form-control" id="" cols="30" rows="5" maxlength="800">{{$property->description}}</textarea>
                                        {{-- <input type="text" name="title" class="form-control" value="{{$property->title}}" placeholder="Personaliza el titulo" /> --}}
                                    </div>
                                    
                                        <div class="form-group-inner">
                                            <div><label>Agente</label> 
                                                <span style="float: right;">
                                                    <label for="noagent">No mostrar</label>
                                                    <input class="input-disabled" id="noagent" class="form-check-input" type="checkbox" name="agent" value="0">
                                                </span>

                                            </div>
                                             <select class="form-control" name="agent" id="select-agent" >
                                                 @foreach ($property->company->users as $user)
                                                 <option {{($property->AgentAssignt->id == $user->id) ? 'selected' : ''}} value="{{$user->id}}">{{$user->f_name}}</option>
                                                 @endforeach
                                             </select>
                                        </div>
                                   
                                    {{-- <div class="col-md-3" style="margin-top: 30px;">
                                        
                                            <label for="noagent">Sin agente</label>
                                            <input id="noagent" class="form-check-input" type="checkbox" name="agent" value="0">
                                       
                                    </div> --}}
                                   
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <div class="courses-area">
                        <div class="container-fluid analysis-progrebar-ctn">
                            <div class="form-group-inner">
                              <div>
                                <label>Selecciona un estilo para las imágenes</label>
                              </div>
                                <span style="display:inline-flex">
                                    <input id="1" type="radio" name="style" value="1">
                                    <label for="1">
                                        <img class="square" src="/images/style4.png" alt="">
                                    </label>
                                </span>

                                <span style="display:inline-flex">
                                    <input type="radio" checked name="style" id="2" value="2">
                                    <label for="2">
                                        <img class="square" src="/images/style3.png" alt="">
                                    </label>
                                </span>
                            
                                <span style="display:inline-flex">
                                    <input type="radio"  name="style" id="3" value="4">
                                    <label for="3">
                                        <img class="square" src="/images/style5.png" alt="">
                                    </label>
                                </span>
                                <span style="display:inline-flex">
                                    <input type="radio" name="style" id="4" value="8">
                                    <label for="4">
                                        <img class="square" src="/images/style6.png" alt="">
                                    </label>
                                </span>
                        </div>
                        <div class="form-group-inner">
                            <div class="row">
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                    <label class="login2 pull-right pull-right-pro">Mostrar / Esconder </label>
                                </div>
                                <div class="col-lg-9 col-md-9 col-sm-9 col-xs-9">
                                    <div class="bt-df-checkbox pull-left">
                                        {{-- <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="i-checks pull-left">
                                                    <label>
                                                            <input type="checkbox" value=""> <i></i> Option one </label>
                                                </div>
                                            </div>
                                        </div> --}}
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="i-checks pull-left">
                                                    <label>
                                                            <input type="checkbox" name="show[]" value="features" checked=""> <i></i> Caracteristicas </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="i-checks pull-left">
                                                    <label>
                                                            <input type="checkbox" name="show[]" value="adtional1" checked=""> <i></i>Recamaras, Baños, Estacionamientos, Area total y construida </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="i-checks pull-left">
                                                    <label>
                                                            <input type="checkbox" name="show[]" value="adtional2" checked=""> <i></i>Frente, Largo, Pisos, Año construcción </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="i-checks pull-left">
                                                    <label>
                                                            <input type="checkbox" name="show[]" value="ubication" checked=""> <i></i> Ubicacion </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="i-checks pull-left">
                                                    <label>
                                                            <input type="checkbox" name="show[]" value="map" checked=""> <i></i> Mapa </label>
                                                </div>
                                            </div>
                                        </div>
                                    
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <div class="text-center">

                            <a role="button" id="btn-cancel" class="btn btn-default" href="{{route('view.property', $property->id)}}">
                                Regresar a la propiedad <i class="fa fa-arrow-circle-left" aria-hidden="true"></i>
                            </a>
                            <button class="btn btn-default" type="submit"><i class="fa fa-print"></i> Generar PDF</button>
                        </div>
                            {{-- <a id="pdf"  class="btn btn-default"  href="#">
                                <i class="fa fa-fw fa-print"></i>  Generar PDF</a> --}}
                            {{--  <div class="holds-the-iframe" >
                                <iframe style="width: 100%; height: -webkit-fill-available" loading="lazy" src="http://brokersconnector.dd/properties/print/{{$property->id}}" frameborder="0"></iframe>
                            </div>  --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.js?version={{ config('app.version')}}" integrity="sha256-yDarFEUo87Z0i7SaC6b70xGAKCghhWYAZ/3p+89o4lE=" crossorigin="anonymous"></script>


    <script>
        $(document).ready(function(){
            $('.input-disabled').click(function(){
                if($(this).is(":checked")){
                    console.log()
                    $(this).parents('.form-group-inner').children('.form-control').prop("disabled", true);
                }
                else if($(this).is(":not(:checked)")){
                    $(this).parents('.form-group-inner').children('.form-control').prop("disabled", false);
                }
            });
        });
    </script>
@endpush

@endsection
