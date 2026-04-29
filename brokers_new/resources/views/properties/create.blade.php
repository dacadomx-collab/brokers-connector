@extends('layouts.app')
@section('title','Agregar propiedad')

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Agregar propiedad</span>
</li>
@endsection

@push('styles')
{{--  <link rel="stylesheet" href="{{ asset('css/dropzone.min.css') }}">  --}}
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/touchspin/jquery.bootstrap-touchspin.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css?version={{ config('app.version')}}">
<style>
    .isDisabled {
        color: currentColor;
        cursor: not-allowed;
        opacity: 0.5;
        text-decoration: none;
    }

    hr{
        border-top: 1px solid #f5f5f5;

        }

        .select2-container--default .select2-selection--single{
            height: 40px !important;
            border-radius: 0px !important;
        }
    

</style>
@endpush
@section('content')

<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">

    {{--  {{$errors}} --}}
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="product-payment-inner-st">

            <ul id="myTabedu1" class="tab-review-design text-center">
                <li class="prop active"  ><a href="#description" id="prop">Propiedad</a></li>
                <li class="addicional"><a href="#advance-information" id="inf_adv">Adicional</a></li>
                <li class="ftrs disabled"><a role="button" class="features isDisabled" id="features" href="">
                        Características </a></li>
                {{--  <li><a id="features" href="#INFORMATION">Cáracteristicas</a></li>  --}}
            </ul>

        </div>
        <form action="{{ url('properties/store') }}" onsubmit="window.onbeforeunload=null" id="form" method="POST" class="add-professors">

            @csrf
            <input type="hidden" name="lat" id="lat" >
            <input type="hidden" name="lng" id="lng" >
          
            <div id="myTabContent" class="tab-content custom-product-edit ">

                <div class="product-tab-list tab-pane fade active in" id="description">
                    @include('properties.utils.tab-general' , ["data" => $property,"edit"=>false ])
                </div>

                {{-- Inicio características --}}
                <div class="product-tab-list tab-pane fade" id="reviews">
                    @include('properties.utils.tab-features' , ["data" => $property,"edit"=>false ])
                </div>

                {{-- Inicio de informacion avanzada --}}
                <div class="product-tab-list tab-pane fade" id="advance-information">
                    @include('properties.utils.tab-addicional' , ["data" => $property,"edit"=>false ])
                </div>

            </div>
        </form>
    </div>
</div>


    @push('scripts')
    <!-- touchspin JS
        ============================================ -->
    <script src="{{ asset('admin/js/touchspin/jquery.bootstrap-touchspin.min.js') }}"></script>
    <script src="{{ asset('admin/js/touchspin/touchspin-active.js') }}"></script>
    <script src="{{ asset('admin/js/icheck/icheck.min.js') }}"></script>
    <script src="{{ asset('admin/js/icheck/icheck-active.js') }}"></script>
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>
    <script>
        var form_prop=false;
    </script>
    <script src="{{ asset('js/properties-control.js') }}"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84&callback=initMap"
        async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script src="{{ asset('js/aiCopywriter.js') }}"></script>

    <script>
        $(document).ready(function() {
         
            //Dejar los datos de locacion despues de la validacion
            @if(old("state"))
                $("#state").select2("val",{{ old("state") }});
            @endif
            @if(old("mun"))
                getTextById({{ old("mun") }}, "/get-mun-id", "#cities");
            @endif
            @if(old("local_id"))
                getTextById({{ old("local_id") }}, "/get-loc-id", "#loc");
            @endif
            
            if(  typeof $("#loc").val() == 'undefined' || $("#loc").val() == null || $("#loc").val() == "" ){
                @if(old("local_id"))
                getTextById({{ old("local_id") }}, "/get-loc-id", "#loc");
                @endif
            }

          
        
        });
       
        //Inicializar el mapa
        var map;
        
        function initMap() {
            var lat={{ old('lat',24.1583354) }}; 
            var lng={{ old('lng',-110.3227886) }};

            map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
            center: {lat: lat, lng: lng},
            streetViewControl: false,
            });

            marker = new google.maps.Marker({
                map: map,
                animation: google.maps.Animation.DROP,
                position: {lat: lat, lng: lng}
            });

            $("#lat").val(lat);
            $("#lng").val(lng);

            //Evento para dar clic en la mapa y dibjar la marca
            google.maps.event.addListener(map, "click", function (e) {

                latLng = e.latLng;
                                    
                // if marker exists and has a .setMap method, hide it
                if (marker && marker.setMap) 
                {
                    marker.setMap(null);
                }

                $("#lat").val(e.latLng.lat());
                $("#lng").val(e.latLng.lng());

                marker = new google.maps.Marker({
                    map: map,
                    animation: google.maps.Animation.DROP,
                    position: {lat: e.latLng.lat(), lng: e.latLng.lng()}
                });

                
            });

           {{--  //  @include('properties.utils.location.change-map')  --}}
        }

        
        @if($errors->any()) 
            form_prop=true;
            @foreach($errors->all() as $error)
            Lobibox.notify('error', {
                        title: 'Error',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: "{{$error}}"
            });
            @endforeach
        @endif
       
        $(document).ready(function () 
        {  
            var modified;
            $("input, select").change(function () {   
                if($(this).val() == '')
                {
                    modified = false;  
                }else{
                    modified = true;  
                }
            });
            function validarCampos() {
            if (modified)  
                return confirm('Puede haber cambios sin guardar en el formulario, ¿Desea salir de todas formas?'); 
            }
            
            window.onbeforeunload = validarCampos;

        }); 

    </script>

    
    @endpush
    @endsection
