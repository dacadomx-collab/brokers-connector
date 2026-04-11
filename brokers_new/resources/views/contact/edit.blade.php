@extends('layouts.app')
@section('title','Editar contacto')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">


@endpush

@section('breadcome')
<li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Editar contacto</span></li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <div class="courses-area" style="padding-bottom:100px;">
                        <div class="container-fluid analysis-progrebar-ctn">
                            {{-- <h1>Editar contacto</h1> --}}
                            <form action="{{ route('contact.update', ['id'=>$contact->id]) }}" method="post"
                                enctype="multipart/form-data" id="form-control">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6 col-xs-6">
                                                <div class="form-group @if($errors->has('name')) has-error @endif">
                                                    <label for="name">Nombre(s) <span
                                                            style="color:red;">*</span></label>
                                                    <input required type="text" class="form-control" maxlength="100"
                                                        name="name" value="{{ $contact->name }}" placeholder="Nombre(s)">
                                                </div>
                                            </div>
                                            <div class="col-md-6 col-xs-6">
                                                <div class="form-group @if($errors->has('surname')) has-error @endif">
                                                    <label for="surname">Apellido(s)</label>
                                                    <input type="text" class="form-control" maxlength="100"
                                                        name="surname" value="{{ $contact->surname }}"
                                                        placeholder="Apellido(s)">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group @if($errors->has('email')) has-error @endif">
                                                    <label for="">Correo electrónico <span
                                                            style="color:red;">*</span></label>
                                                    <input required type="email" class="form-control" maxlength="100"
                                                        name="email" value="{{ $contact->email }}"
                                                        placeholder="Ingresar un correo elecroníco">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group @if($errors->has('job')) has-error @endif">
                                                    <label for="surname">Puesto</label>
                                                    <input type="text" class="form-control" maxlength="100" name="job"
                                                        value="{{ $contact->job }}" placeholder="Puesto de trabajo">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group @if($errors->has('address')) has-error @endif">
                                                    <label for=""> Dirección</label>
                                                    <input maxlength="255" type="text" class="form-control"
                                                        name="address" value="{{ $contact->address}}"
                                                        placeholder="Ingresar una dirección">
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
                                                            <option {{$index == $contact->origin ? 'selected' : ''}} value="{{$index}}">{{$origin}}</option>
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
                                                            <option {{$index == $contact->status ? 'selected' : ''}} value="{{$index}}">{{$origin}}</option>
                                                            @endforeach
                                                        </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group @if($errors->has('type')) has-error @endif">
                                                    <label for=""> Tipo de contacto</label>
                                                    <select class="form-control custom-select-value" name="type">
                                                            <option value="0">Seleccione una opción</option>
                                                            @foreach (config('app.contact_types') as $index => $types)
                                                            <option {{$index == $contact->type ? 'selected' : ''}} value="{{$index}}">{{$types}}</option>
                                                            @endforeach
                                                        </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group @if($errors->has('otros')) has-error @endif">
                                                    <label for=""> Datos adicionales</label>
                                                    <input type="text" maxlength="255" class="form-control" name="otros"
                                                        value="{{ $contact->otros}}"
                                                        placeholder="Escribe mas detalles del contacto">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group phonediv @if($errors->has('phone')) has-error @endif"
                                                id="phone-div">
                                                @if($phones->count() != 0)
                                                @foreach($phones as $key=>$phonex)
                                                <div id="phone-{{$phonex->id}}" class="row">
                                                    <div class="col-md-5 col-xs-5" style="margin-bottom:15px;">
                                                        <label for="">Teléfono</label>
                                                        <input type="text" class="form-control phone-input" maxlength="13"
                                                        name="phone[{{$phonex->id}}][phone]" value="{{ $phonex->phone}}"
                                                        placeholder="Teléfono">
                                                    </div>
                                                    <div class="col-md-5 col-xs-5">
                                                        <label for="">Tipo</label>
                                                        <select class="form-control custom-select-value"
                                                        name="phone[{{$phonex->id}}][type]" value="{{ $phonex->type }}">
                                                        <option value="{{ $phonex->type }}">{{ $phonex->type }}</option>
                                                        <option value="Casa">Casa</option>
                                                        <option value="Celular">Celular</option>
                                                        <option value="Trabajo">Trabajo</option>
                                                        <option value="Trabajo">Oficina</option>
                                                    </select>
                                                    </div>
                                                    @if($key == 0) 
                                                    <div class="col-md-2" style="padding-top: 27px;">
                                                        
                                                        <button type="button" class="btn btn-primary dynamic-add"
                                                            onclick="agregarCampo()"><i
                                                                class="fa fa-fw fa-plus-circle"></i></button>
                                                    </div>
                                                    @endif
                                                    <div class="col-md-1 col-xs-1 " style="padding-top: 27px;">
                                                        @if($key != 0) 
                                                        <button onclick="borrarElemento({{$phonex->id}})" class="btn btn-danger"><i
                                                            class="fa fa-fw fa-times"></i></button>
                                                        @endif
                
                                                    </div>
                                                </div>
                                                @endforeach
                                                @else
                                                <div  id="phone-" class="row">
                                                    <div class="col-md-5 col-xs-5" style="margin-bottom:15px;">
                                                        <label for="">Teléfono </label>
                                                        <input  type="text" class="form-control phone-input" maxlength="20" name="phone[0][phone]" value="" placeholder="Escriba numero telefonico">
                                                    </div>
                                                    <div class="col-md-5 col-xs-5">
                                                        <label for="">Tipo</label>
                                                        <select class="form-control custom-select-value"
                                                            name="phone[0][type]">
                                                            <option value="">Elija tipo de télefono</option>
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
                                                                class="fa fa-fw fa-trash-o"></i></button></div> </div> --}}
                                                </div>
                                                @endif
                                            </div>

                                        </div>
                                    </div>
                                    <div class="analysis-progrebar-ctn">
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center"
                                            style="margin-top:35px;">
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
                </div>{{--   fin --}}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script src="{{ asset('js/jquery.validate.js') }}"></script>
<script>

document.getElementById('btn-cancel')
        .addEventListener('click', function(){
            window.location.href = '/home/contact/home';
        })

    $("#phone-div").on("keypress", '.phone-input', function (e) {
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
            }
        });
    var x = 999999999{{$phones->count()}}; 
 function agregarCampo(){
    
    var addButton = $('.add_button'); 
    var wrapper = $('.phonediv'); 
    var br ="";
    if(x==999999999{{$phones->count()}})
    {
        var br = '<span id="br-telefono">';
    }
    var fieldHTML = br + '<div id="phone-'+ x +'" class="form-group @if($errors->has(" phone")) has-error @endif "> <div class=" row"> <div class="col-md-5" style="margin-bottom:15px;"> <label for="">Teléfono</label> <input type="text" class="form-control phone-input" maxlength="10" name="phone['+x+'][phone]" value="" placeholder="Teléfono"> </div><div class="col-md-5"><label for="">Tipo</label> <select class="form-control custom-select-value" name="phone['+x+'][type]"> <option value="">Elija tipo de télefono</option> <option value="Casa">Casa</option> <option value="Celular">Celular</option> <option value="Trabajo">Trabajo</option> <option value="Trabajo">Oficina</option> </select> </div><div class="col-md-2 col-xs-2" style="padding-top: 27px;"><button onclick="borrarElemento('+x+')" class="btn btn-danger"><i class="fa fa-fw fa-minus-circle"></i></button></div></div></div>';

    if(x > 9999999993){
        alert("Numero de telefonos maximo");
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
