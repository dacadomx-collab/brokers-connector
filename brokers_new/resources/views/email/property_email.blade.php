@extends('layouts.app')
<!--#05526a;-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/select2.min.css') }}"  />
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">

<style>
    .preview{
        background-color: aquamarine;
        padding: 1em;
    }
    p{
        overflow-wrap: break-word;
    }
    .info ul{
        list-style-type: none;
        margin: 0;
        padding: 0;
    }
    .info li{
        display: inline-block;
        padding: 8px;
        margin: 4px;
        background-color: #efefef;
        color: #000000;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        
        text-align: center;
    }
    .info li img{
    
        height: 30px;
        width: 30px;
    }
    .select2-container--default .select2-selection--multiple {
        border-radius: 0;
        background-color: #FFFFFF;
        background-image: none;
        border: 1px solid #ccc;
        color: inherit;
        display: block;
        padding: 6px 12px;
        transition: border-color 0.15s ease-in-out 0s, box-shadow 0.15s ease-in-out 0s;
        width: 100%;
        height: 40px;
        box-shadow: none;
    }

</style>

@endpush
@section('title', 'Enviar propiedades por email')
@section('breadcome')
    <li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
    <li><a href="{{ url('properties/index') }}">Propiedades</a><span class="bread-slash">/</span></li>
  
    <li><a href="#">Enviar Propiedades</a></li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <div class="courses-area" style="padding-bottom:100px;">
                        <form id="form" action="{{route('email.send')}}" method="post">
                        <div  class="container-fluid analysis-progrebar-ctn">
                            <div class="row">
                                <div class="col-md-7">
                                    @csrf
                                        
                                    <div class="form-group">
                                        <label for="" style="margin-top: 0.5em;">Para <span class="text-danger">*</span></label>
                                  

                                        <div class="row">
                                            <div class="col-md-12">
                                          
                                                <select class="form-control select2" required multiple="multiple" data-placeholder="Selecciona uno o varios contactos"
                                                style="width: 100%; " name="email[]">
                                                @foreach ($contacts as $contact)
                                                <option value="{{$contact->id}}">{{$contact->name}} ({{$contact->email}})</option>
                                                @endforeach
                                            
                                        </select>
                                           
                                          
                                            </div>
                                          </div>
                                    </div>
                                  
                                    @if( $contacts->count() == 0)
                                        <p style="color:red;">No tienes contactos, <a href="{{route('create.contact')}}">agregar contacto.</a></p> 
                                    @endif
                                    <div class="form-group">
                                        <label for="">Asunto <span class="text-danger">*</span></label>
                                        <input name="asunto" type="text" class="form-control" placeholder="ej. Propiedades solicitadas" value="" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="">Mensaje</label>
                                        <textarea name="message" id="textmessage" class="form-control" cols="30" rows="10" placeholder="Escriba un mensaje para el destinatario"></textarea>
                                       
                                    </div>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <div class="form-group">
                                                
                                                <input name="color" readonly autocomplete="off" type="text" id="my-colorpicker1" style="display:none;" value="#05526a" class="color form-control my-colorpicker1 colorpicker-element">
                                            </div>
                                        </div>
                                        <div class="col-xs-6">
                                                <div class="form-group">
                                                    
                                                    <input name="color" readonly autocomplete="off" type="text" id="my-colorpicker2" style="display:none;" class="color form-control my-colorpicker1 colorpicker-element">
                                                </div>
                                        </div>
                                     
                                    </div>

                                
                         
                                   
                                </div>
                               

                                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-12">
                                    <div class="single-review-st-item res-mg-t-30 table-mg-t-pro-n">
                                        <div class="single-review-st-hd">
                                            <h2>Propiedades seleccionadas</h2>
                                        </div>
                                        <hr>
                                        <div class="text-right">
                                            <span class="info">Maximo de 10</span>
                                        </div>

                                                <div class="text-center" id="div-alert" style="@if($properties->count()>0)  display:none; @endif">
                                                    
                                                            No hay propieades seleccionadas
                                                    
                                                </div>
                                                @if (Session::has('email-properties'))
                                                <div class="single-review-st-text hoverq text-right" style="width:100%; display:block;">
                                                    <button type="button" class="btn btn-danger btn-sm btn-remove-all">Quitar todo <i class="fa fa-eraser" aria-hidden="true"></i></button>
                                                </div>

                                                @endif
                                              
                                               
                                                @foreach($properties as $property)
                                               
                                                <div class="single-review-st-text hoverq row"id="div-property{{$property->id}}">
                                                        <div class="col-md-10" style="display: flex;width:100%">
                                                        
                                                                <div style="width:20%;">
                                                                    <img src="{{$property->image}}" alt="" style="width:100%;">
                                                                </div>
                                                                <div class="review-ctn-hf" style="width:70%;">
                                                                    @if (auth()->user()->company_id==$property->company->id)
                                                                    <a target="_blank" href="{{ url('properties/view/'.$property->id.'-'.\Illuminate\Support\Str::slug($property->title)) }}">
                                                                    @else
                                                                    <a target="_blank" href="{{ url('stock/view/'.$property->id) }}?contact_email={{ auth()->user()->email }}">
                                                                    @endif
                                                                    
                                                                    <h3>{{ $property->title }}</h3>
                                                                    <p>{{ $property->address }}</p>
                                                                    </a>
                                                                <input type="hidden" value="{{$property->id}}" name="checkbox_property[]">
                                                                </div>
                                                            
                                                            </div>

                                                            <div class="text-right col-md-2">
                                                               
                                                                <button type="button" class="btn btn-danger btn-sm btn-remove-property" value="{{$property->id}}">
                                                                    <i class="fa fa-times" aria-hidden="true"></i>
                                                                </button>
                                                                
                                                            </div>
                                                    </div>
                                                
                                                @endforeach

                                                <div class="text-center" style="margin-top:10px;">
                                                    <a  href="{{route('show.all.properties')}}" role="button" class="btn btn-default text-center" style="display: inline-flex; ">
                                                        <i class="fa fa-home fa-icon"></i>
                                                        Agregar desde <br> mis propiedaes</a>
                                                        &nbsp;&nbsp;
                                                    <a  href="{{route('show.all.stock')}}" role="button" class="btn btn-default" style="display: inline-flex;">
                                                        <i class="fa fa-globe fa-icon"></i> &nbsp;
                                                        Agregar desde <br> la bolsa</a>

                                                </div>

                                        
                                       
            
            
                                           
                                            
                                        {{-- </div> --}}
                                      
                                    </div>
                                    
                              
                                </div>
                                
                           
                              
                            </div>
                            <div class="row" style="padding-top: 2rem;">
                                    <div  align="center" class="col-xs-12">

                                        {{--email.send 
                                        <a id="btn-style-1" target="_blank"  class="btn btn-default"><i class="fa fa-fw fa-eye"></i> Vista previa</a>
                                        <a  href="/properties/index/"  class="btn btn-default  ">Regresar</a>
                                        <button type="submit"class="btn btn-primary ">Enviar email</button>
                                        --}}
                                        {{--  <a data-fancybox id="btn-style-1" data-type="iframe" class="btn btn-default" data-src="http://brokersconnector.test/properties/preview/500?style=1&color=05526a&colorLetra=" href="javascript:;">
                                            <i class="fa fa-fw fa-eye"></i>  Vista previa
                                          </a>  --}}
                                       
                                        <a  style="cursor:pointer;" class="btn btn-default btn-return">
                                            Cancelar y salir &nbsp;<i class="fa fa-arrow-left"></i>
                                        </a>
                                       {{-- <a id="btn-style-1" target="_blank"  class="btn btn-default">Vista previa <i class="fa fa-fw fa-eye"></i></a>
--}}
                                        <button type="submit" id="btn-save" class="btn btn-primary waves-effect waves-light">
                                                Enviar email &nbsp;<i class="fa fa-check"></i>
                                        </button>
                                    </div>
                            </div>
                        </div>
                    <input type="hidden" name="page" value="{{ $request->page }}">
                  
                    </form>
                    </div>
                </div>{{--   fin --}}
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/jquery.validate.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.js?version={{ config('app.version')}}" integrity="sha256-yDarFEUo87Z0i7SaC6b70xGAKCghhWYAZ/3p+89o4lE=" crossorigin="anonymous"></script>
<script src="{{ asset('js/selec2.js') }}"></script>
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script>
    @if (Session::has('fail'))
   
        Lobibox.notify('error', {
            title: 'Error',
            position: 'top right',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            sound: false,
            msg: "{{session('fail')}}"
        });



    @endif
    @if (Session::has('success'))
   
        Lobibox.notify('success', {
            title: 'Notificacion',
            position: 'top right',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            sound: false,
            msg: "{{session('success')}}"
        });



    @endif

    $(document ).ready(function() {

        //Evento para regresar a la  vista anterior
        $(".btn-return").click(function(){
                //borrar la sesion
                $.ajax({
                url: '{{ route("email.deletesession") }}',            
                type: 'get',   
                    success: function (res) {
                    
                        window.location.href = "{{ url('properties/index') }}";
                    
                    },        
                });
        });

        //Evento para remover todo
        $(".btn-remove-all").click(function(){
                //borrar la sesion
                window.location.href = "{{ url('properties/email') }}";
        });

        //Evento para remover la propiedad
        $(".btn-remove-property").click(function(){
            var element_id=$(this).val();
           
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                url: '{{ route("email.removeproperty") }}',            
                type: 'post',
                data: {id:element_id}, 

                    success: function (res) {
                    
                        $("#div-property"+element_id).fadeOut();
                        Lobibox.notify('success', {
                            title: 'Notificacion',
                            position: 'top right',
                            showClass: 'fadeInDown',
                            hideClass: 'fadeUpDown',
                            sound: false,
                            msg: res.msg
                        });

                        window.location.href = res.url;
                    
                    },        
            });                                                                                                                             

        });

        //Inicializar el select multiple de contactos
        $('.select2').select2();
    });

</script>



@endpush