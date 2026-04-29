@extends('layouts.app')
@section('title','Editar propiedad '.$property->title)

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Editar propiedad {{$property->title}}</span>
</li>
@endsection

@push('styles')
{{--  <link rel="stylesheet" href="{{ asset('css/dropzone.min.css') }}">  --}}
<link rel="stylesheet" href="{{ asset('admin/css/touchspin/jquery.bootstrap-touchspin.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css?version={{ config('app.version')}}">
<style>
    .btn-custon-four{
        background: white;
        border-color: #ccc;
        min-width: 41px;
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
                <li class="ftrs"><a role="button" class="features" id="features" href="#reviews">
                        Caracteristícas </a></li>
                        
            </ul>

            <div class="text-right">
                <a href="{{route('add.images.properties', $property->id)}}" data-toggle="tooltip" title="Ir a imágenes y videos" class="btn btn-custon-four"><i class="fa fa-image" ></i></a>
            </div>
            
        </div>
        <form action="{{ url('properties/update') }}" id="form" method="POST" class="add-professors">

            @csrf
            <input type="hidden" name="lat" id="lat" >
            <input type="hidden" name="lng" id="lng" >
            <input type="hidden" name="property_id" value="{{$property->id}}">
            <div id="myTabContent" class="tab-content custom-product-edit">
                
                {{-- Inicio de informacion general --}}
                <div class="product-tab-list tab-pane fade active in" id="description">
                    @include('properties.utils.tab-general',["data"=>$property,"edit"=>true])
                </div>

                
                {{-- Inicio de informacion avanzada --}}
                <div class="product-tab-list tab-pane fade" id="advance-information">
                    @include('properties.utils.tab-addicional', ["data"=>$property,"edit"=>true])
                </div>
                
                {{-- Inicio características --}}
                <div class="product-tab-list tab-pane fade" id="reviews">
                    @include('properties.utils.tab-features', ["data"=>$property,"edit"=>true])
                </div>

                <div class="text-center" style="margin-top:15px;">
                    <button type="button" id="save-property" class="btn btn-primary waves-effect waves-light">
                        Guardar &nbsp; <i class="fa fa-check"></i>
                    </button>
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
        var form_prop=true;
    </script>
    <script src="{{ asset('js/properties-control-edit.js') }}"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84&callback=initMap"
        async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script src="{{ asset('js/aiCopywriter.js') }}"></script>

    <script>
        //Dejar los datos de locacion despues de la validacion
        {{--  //{{dd($property->local->city->id)}}  --}}
        
         $("#state").val({{ old("state" , $property->local->city->state->id) }});
         
         getTextById( {{old("mun", $property->local->city->id)}}, "/get-mun-id", "#cities");
         
         getTextById( {{old("local_id", $property->local->id)}}, "/get-loc-id", "#loc");

         if(  typeof $("#loc").val() == 'undefined' || $("#loc").val() == null || $("#loc").val() == "" ){
                
            getTextById({{ old("local_id", $property->local->id) }}, "/get-loc-id", "#loc");

            }
        
       var map;

        function initMap() {
            var lat={{old('lat',$property->lat)!='' ? old('lat',$property->lat) : 24.1583354}};
            var lng={{old('lng',$property->lng)!='' ? old('lng',$property->lng) : -110.3227886 }};

             map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
            center: {lat: lat, lng: lng},
            disableDefaultUI: true,
            });

            marker = new google.maps.Marker({
                map: map,
                animation: google.maps.Animation.DROP,
                position: {lat: lat, lng: lng}
            });

            $("#lat").val(lat);
            $("#lng").val(lng);
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
            {{--  @include('properties.utils.location.change-map')  --}}
        }


        @if($errors->any()) 
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
    

        
    </script>
 


    {{-- General --}}
    
    
    @endpush
    @endsection
