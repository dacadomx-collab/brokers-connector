@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/select2/select2.min.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.css?version={{ config('app.version')}}" integrity="sha256-P8k8w/LewmGk29Zwz89HahX3Wda5Bm8wu2XkCC0DL9s=" crossorigin="anonymous" />
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<style>
.fancybox-slide--iframe .fancybox-content {
    width  : 100%;
    height : 100%;
    max-width  : 100%;
    max-height : 100%;
   
}
/*codigo para el menu de arriba*/
.mfp-zoom-out-cur {
        cursor: pointer;
        }
        .mfp-zoom-out-cur .mfp-image-holder .mfp-close {
        cursor: pointer;
        }
        .container-image {
            position: relative;
            width: 100%;
            height: 250px;
            /*box-shadow: 2px 9px 20px;*/
          /*  cursor: pointer;*/
           /* margin-bottom: 25px;*/
         
          }
          .select-image{
            background: #3077b7;
            padding: 10px;
          }
          
          .image {
              display: block;
              width: 100%;
              height: -webkit-fill-available;
              object-fit: contain;
            }
          
          .overlay {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100%;
            width: 100%;
            opacity: 1;
            transition: .3s ease;
            background-color:transparent;
            cursor: pointer;
          
          }
          .container-image:hover .overlay {
            opacity: 1;
          }
          
          .text {
            color: white!important;
            //font-size: medium;
            
            position: absolute;
            width:100%;
            top: 50%;
            left: 50%;
            -webkit-transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            text-align: center;
          }
          .options{
          
            position: fixed;
            z-index: 100;
            width:100%;
            background: #3077b7;
            color: white;
            top:0;
            margin-top:0px;
            display:none;
            padding: 13px 20px;
          }
          .btn-transparent{
            color: #ffffff;
            background-color: #fff0;
            border-color: #ffffff;
            min-width: 150px;
            margin: 0 10px;
          }
          .btn-transparent:hover{
            color: #000;
            background-color: white;
            border-color:#3077b7;
           
          }
          .btn-transparent:hover i.fa-star{
           color: #ff9800 !important;
           
          }
          .btn-transparent:hover i.fa-eye{
           color: #3077b7 !important;
           
          }
          .btn-transparent:hover i.fa-times{
           color: #f44336 !important;
           
          }
          .featured{
           position: absolute;
           z-index: 10;
           margin: 2px 22px;
           right:0;
           color: #ffeb3b;
           font-size: x-large;
          }

    </style>
</style>
@endpush

@section('title', $properties->total().' propiedades encontradas')

@section("company_info")
<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12  row form-inline" style="margin:15px;">
    
    <div class="pull-left" style="padding-top:35px;">
        <a href="{{route('show.all.stock')}}">
            <i class="fa fa-arrow-left fa-2x" aria-hidden="true"></i>
        </a>
    </div>
    <div class="text-center">
        <img src="{{$company->logo_image}}" style="width:auto; height:100px;"> 
        <span style="font-size:25px;">
            {{$company->name}}
        </span>

    </div>
</div>


@stop

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><a href="{{ url('stock/index') }}">Bolsa Inmobiliaria</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">{{$company->name}}</span>
</li>
@endsection

@section('content')
<div id="options" class="options">
    <div class="row">
        <div class="col-xs-11">

            <button type="button" id="share-button" class="btn btn-delete-file btn-transparent"><i class="fa fa-share"></i> Enviar por email</button>
            </span>

        </div>
    </div>
</div>
<div class="container" style="width:100%;">
  
@include('stock.includes.filter', ["url"=>route('search.stock.company', $company->id), "disMyProperties" => true, "url_clean"=>route("view.stockCompany", $company->id)])


<form id="form-propertys" name="form-propertys" method="POST"  autocomplete="off" action="{{ route('view.email') }}">
@csrf
<input type="hidden" name="page" value="bolsa">
<div class="container-fluid">
    @foreach ($properties as $property)
        @if (Session::has('view-stock'))
        @if (Session::get('view-stock'))
            @include('stock.includes.stock-list')
        @else
            @include('stock.includes.stock-grid')
        @endif
        @else
            @include('stock.includes.stock-grid')
        @endif
    @endforeach
        </div>
        </form>
            @if($properties->count() == 0)
            <div class="row">
                <div class="col-md-12">
                    <div class="" align="center">
                        <h3></h3>
                        <div class=" h-100 d-flex justify-content-center align-items-center">
                            <div class="container theme-showcase" role="main">
                              
                                <div class="jumbob">
                                    <h2 style="">No se encontraron propiedades<i class="fa fa-sad-tear"></i></h2>
                                </div>
                                @else
                                
                            </div>
                            <div align="center">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif


</div>

<div class="col text-center">
    {{$properties->appends(Request::except('page'))->links()}}
</div>

@stop

@push('scripts')
<script src="{{ asset('js/selec2.js') }}"></script>
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.js" integrity="sha256-yDarFEUo87Z0i7SaC6b70xGAKCghhWYAZ/3p+89o4lE=" crossorigin="anonymous"></script>
<script src="{{ asset('admin/js/icheck/icheck.min.js') }}"></script>
<script src="{{ asset('admin/js/icheck/icheck-active.js') }}"></script>

<script>
     $('.chosen-select').select2({closeOnSelect:false});

       //Inicializar el selector de ciudades
       $('#cities').select2({
            ajax: {
                delay: 500,
                url: '{{route("property.city.filter")}}',
                data: function (params) {
                   
                    var queryParameters = {
                    q: params.term,
                        
                }

                    return queryParameters;
            },
            processResults: function (data) {
                
                    return data;
                    
                }
            },
            placeholder:"Elegir ciudad / municipio",
            language: {
                noResults: function() {
                    return "No hay resultado";        
                },
                searching: function() {
                    return "Buscando..";
                }
            }
                
        });

        //Metodo para dejar datos de ciuadaes al filtrar
        @isset($old_inputs, $old_inputs['city'])
            getTextById({{ $old_inputs['city'] }}, "/get-mun-id", "#cities");
        @endisset
        
        //Funcion para obtener la ciudad elegida al filtrar
        async function getTextById(id, url, select_id)
        {
            const berni =  await $.ajax({
                    url: url,
                    data: {id:id},                
                    type: 'get',   
                    success: function (res) {
                        $(select_id).empty().append('<option value="'+res.id+'">'+res.text+'</option>').val(res.id).trigger('change');
                    },      
                });
        }

    /*Script para los checkbox  y mostrar el menu para compartir por correo*/
           $('input').iCheck({
    checkboxClass: 'icheckbox_square-green',
		radioClass: 'iradio_square-green',
        });
    const optionsBar = $('#options');
    const images = $('.img');
    
    $('.checkbox_property').on('ifChanged', function(event, from){
        $(this).toggleClass('select-image');
        if($('.select-image').length){
            optionsBar.stop(true, true).fadeIn();
            if ($('.select-image').length > 1) {
                $('#options-single').fadeOut();
            }else{
                $('#options-single').stop(true, true).fadeIn();
            }
        }else{
            optionsBar.fadeOut();
        }
    
    });
    $('.btn-open').click(function(){
            
        $('.select-image').first().children('div').children('a').trigger('click');

    });
    $('#share-button').click(function(){
    const form = document.getElementById('form-propertys');
    console.log(form);
    form.submit(); });
</script>
    
    <script>
        $(".price").keypress(function (e) {
            if (e.which != 8 && e.which != 0 && e.which != 46 && (e.which < 48 || e.which > 57)) {
                return false;
            } else {
                if (e.which == 44) {
                    return false;
                } else if (e.which == 46) {
                    if (this.value.split('.').length >= 2) {
                        return false;
                    }
                }
            }
        });

    
        $('#button-advanced').click(function () {
            $('#advanced').fadeToggle();
        })

        $(".click-view").click(function(){
            let ruta = $(this).data("url");
            $.fancybox.open({
                src  : ruta,
                type : 'iframe',
                opts : {
                    afterShow : function( instance, current ) {
                        console.info( 'done!' );
                    }
                }
            });

        })
    </script>
    @if (Session::has('success'))
    <script>
        Lobibox.notify('success', {
            title: 'Notificación',
            position: 'top right',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "{{session('success')}}"
        });
    
       
    </script>
    @endif
@endpush