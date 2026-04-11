@extends('layouts.app')
@section('title','Agregar contacto')
@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">


@endpush

@section('breadcome')
    <li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
    <li><span class="bread-blod">Contacto</span></li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <div class="courses-area">
                        <div class="container-fluid analysis-progrebar-ctn">
                            {{-- <h1>Agregar contacto</h1> --}}
                            <form action="{{ route('create.contact') }}" method="post" enctype="multipart/form-data"
                                id="form-control">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6 col-xs-6">
                                                <div class="form-group @if($errors->has('name')) has-error @endif">
                                                    <label for="name">Nombre(s)<span
                                                        style="color:red;">*</span> </label>
                                                    <input required type="text" class="form-control" maxlength="150" name="name" value="{{old('name')}}" placeholder="Nombre(s)">
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-xs-6">
                                                <div class="form-group @if($errors->has('surname')) has-error @endif">
                                                    <label for="surname">Apellido(s)</label>
                                                    <input type="text" class="form-control" maxlength="150" name="surname" value="{{old('surname')}}" placeholder="Apellido(s)">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group @if($errors->has('email')) has-error @endif">
                                                    <label for="">Correo electroníco <span
                                                            style="color:red;">*</span></label>
                                                    <input required type="email" class="form-control" maxlength="100" name="email" value="{{old('email')}}" placeholder="Ingresar un correo electrónico">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group @if($errors->has('job')) has-error @endif">
                                                    <label for="surname">Puesto</label>
                                                    <input type="text" class="form-control" maxlength="100" name="job" value="{{old('job')}}" placeholder="Puesto de trabajo">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group @if($errors->has('address')) has-error @endif">
                                                    <label for=""> Dirección</label>
                                                    <input maxlength="255" type="text" class="form-control" name="address" value="{{old('address')}}" placeholder="Ingresar una dirección">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">

                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group @if($errors->has('origin')) has-error @endif">
                                                    <label for=""> Origen de contacto</label>
                                                    <select class="form-control custom-select-value" name="origin">
                                                            <option value="0">Seleccione una opción</option>
                                                            @foreach (config('app.contact_origins') as $index => $origin)
                                                            <option value="{{$index}}">{{$origin}}</option>
                                                            @endforeach
                                                        </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group @if($errors->has('status')) has-error @endif">
                                                    <label for=""> Estatus de contacto</label>
                                                    <select class="form-control custom-select-value" name="status">
                                                            <option value="0">Seleccione una opción</option>
                                                            @foreach (config('app.contact_statuses') as $index => $origin)
                                                            <option value="{{$index}}">{{$origin}}</option>
                                                            @endforeach
                                                        </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group @if($errors->has('type')) has-error @endif">
                                                    <label for=""> Tipo de contacto</label>
                                                    <select class="form-control custom-select-value" name="type">
                                                            <option value="0">Seleccione una opción</option>
                                                            @foreach (config('app.contact_types') as $index => $origin)
                                                            <option value="{{$index}}">{{$origin}}</option>
                                                            @endforeach
                                                        </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group @if($errors->has('otros')) has-error @endif">
                                                    <label for=""> Datos adicionales</label>
                                                    <input type="text" maxlength="255" class="form-control" name="otros" value="{{old('otros')}}" placeholder="Escribe más detalles del contacto">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group phonediv @if($errors->has('phone')) has-error @endif"
                                                id="phone-div">
                                                <div class="row">
                                                    <div class="col-md-5 col-xs-5" style="margin-bottom:15px;">
                                                        <label for="">Teléfono</label>
                                                        <input  type="text" class="form-control phone-input" maxlength="20" name="phone[0][phone]" value="" placeholder="Escriba número telefónico">
                                                    </div>
                                                    <div class="col-md-5 col-xs-5">
                                                        <label for="">Tipo</label>
                                                        <select class="form-control custom-select-value"
                                                            name="phone[0][type]">
                                                            <option value="">Elija tipo de teléfono</option>
                                                            <option value="Casa">Casa</option>
                                                            <option value="Celular">Celular</option>
                                                            <option value="Trabajo">Trabajo</option>
                                                            <option value="Trabajo">Oficina</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 col-xs-2" style="padding-top: 27px;">
                                                            <button type="button"  class="btn btn-primary dynamic-add"
                                                             onclick="agregarCampo()"><i class="fa fa-fw fa-plus-circle"></i></button>
                                                    </div>
                                                    {{-- <div class="col-md-2 col-xs-2" style="padding-top: 27px;"><button
                                                            onclick="borrarElemento('+x+')" class="btn btn-danger"><i
                                                                class="fa fa-fw fa-trash-o"></i></button></div>
                                                    </div> --}}
                                                </div>
                                            </div>
                                            
                                        </div>
                                    </div>
                                </div>
                                <div class="analysis-progrebar-ctn">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="margin-top:35px;">
                                                <button type="button" id="btn-cancel" class="btn btn-default">
                                                        Cancelar &nbsp;<i class="fa fa-times"></i>
                                                    </button>&nbsp;
                                                    <button type="button" id="btn-save"
                                                        class="btn btn-primary waves-effect waves-light">
                                                        Guardar &nbsp;<i class="fa fa-check"></i>
                                                    </button>
                                        </div>
                                </div>
                            </form>
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
<script src="{{ asset('js/jquery.validate.js') }}"></script>
{{-- input-mask JS — desactivado
<script src="{{ asset('admin/js/input-mask/jasny-bootstrap.min.js') }}"></script>
--}}
<script>
    $(document).ready(function(){
        $("#phone-div").on("keypress", '.phone-input', function (e) {
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {

            return false;
            }
        });


        document.getElementById('btn-cancel')
        .addEventListener('click', function(){
            window.location.href = '/home/contact/home';
        })

    });

 var x = 9999999990; 
 function agregarCampo(){
    
    var addButton = $('.add_button'); 
    var wrapper = $('.phonediv'); 
    var br ="";
    if(x==9999999990)
    {
        var br = '<span id="br-telefono">';
    }

    var fieldHTML = br + '<div id="phone-'+ x +'" class="form-group @if($errors->has(" phone")) has-error @endif "> <div class=" row"> <div class="col-md-5" style="margin-bottom:15px;"> <label for="">Teléfono</label> <input type="text" class="form-control phone-input" maxlength="10" name="phone['+x+'][phone]" value="" placeholder="Teléfono"> </div><div class="col-md-5"><label for="">Tipo</label> <select class="form-control custom-select-value" name="phone['+x+'][type]"> <option value="">Elija tipo de télefono</option> <option value="Casa">Casa</option> <option value="Celular">Celular</option> <option value="Trabajo">Trabajo</option> <option value="Trabajo">Oficina</option> </select> </div><div class="col-md-2 col-xs-2" style="padding-top: 27px;"><button onclick="borrarElemento('+x+')" class="btn btn-danger"><i class="fa fa-fw fa-minus-circle"></i></button></div></div></div>';

    if(x > 9999999991){
        alert("Numero de teléfonos maximo");
    }else{
        $(wrapper).append(fieldHTML); // Add field html
        x++;
     
    }
   
  } 
  function borrarElemento(elemt){
    var id_element= "phone-"+elemt;
    console.log(id_element);
    $('#'+id_element).remove();
    x--;
    if(x==9999999990)
    {
        $("#br-telefono").remove();
    }
  } 
</script>
@if (Session::has('success'))
<script>
    Lobibox.notify('success', {
        title: 'Información actualizada',
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

@if ($errors->any())
<script>
     @foreach($errors->all() as $error)
        Lobibox.notify('error', {
            title: 'Error',
            verticalOffset: 5,
            position: 'top right',
            height: 'auto',      
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "{{$error}}"
            
        });
     @endforeach
    
   
        
   
</script>
@endif

<script>
    $("#btn-save").click(function(){
        if (!$("#form-control").valid()) 
            return
        
        $("#form-control").submit();
    })

    
</script>
@endpush
