@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">

    <link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
@endpush

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Perfil</span>
</li>
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
                                <form action="{{route('update.profile')}}" method="post" enctype="multipart/form-data" id="form-control">
                                    @csrf
                                        <div class="form-group col-md-12">
                                            <button type="button" class="btn btn-success btn-lg pull-right btn-save" >Actualizar</button>

                                        </div>
                                        <div class="col-md-8">
                                                
                                            <label for="">Correo:</label>
                                            <p>{{$user->email}}</p>
                                            <br>
                                            <div class="form-group @if($errors->has('full_name')) has-error @endif">
                                                <label for="">Nombre</label>
                                                <input required type="text" class="form-control" maxlength="255" name="full_name" value="{{old('full_name', $user->full_name)}}"
                                                    placeholder="Ingresar Nombre(s)">
                                            </div>
                                            <br>
                                           
                                            <div class="form-group @if($errors->has('last_name')) has-error @endif">
                                                <label for="">Apellido(s)</label>
                                                <input required type="text" class="form-control" maxlength="255" name="last_name" value="{{old('last_name', $user->last_name)}}"
                                                    placeholder="Ingresar apellido paterno + apellido materno">
                                            </div>
                                            <br>
                                            <div class="form-group @if($errors->has('title')) has-error @endif">
                                                <label for="">Titulo</label>
                                                <input required type="text" class="form-control" maxlength="255" name="title" value="{{old('title', $user->title)}}"
                                                    placeholder="Ingresar un título que identique al usaurio">
                                            </div>
                                            <br>
                                            <div class="form-group @if($errors->has('name')) has-error @endif">
                                                <label for="">Nombre de usuario</label>
                                                <input required type="text" class="form-control" maxlength="255" name="name" value="{{old('name', $user->name)}}"
                                                    placeholder="Ingresar un nombre de usuario">
                                            </div>
                                            <br>
                                            <div class="form-group @if($errors->has('phone')) has-error @endif">
                                                <label for="">Télefono</label>
                                                <input required type="text" class="form-control" maxlength="10" name="phone" value="{{old('phone', $user->phone)}}"
                                                    placeholder="Ingresar un teléfono">
                                            </div>
                                            
                                        </div>
                                        
                                        <div class="col-md-4">
                                                
                                            <label for="">Foto:</label>
                                            <br>
                                            @if($user->avatar!="")
                                                <img src="{{$user->avatar}}"/>
                                            @else
                                                <img class="img-thumbnail" style="max-width: 100%; height: 100px;" src="{{ asset('img/profile/sin-avatar.png') }}" alt="" />
                                            @endif
                                            <br>
                                            <div class="file-upload-inner file-upload-inner-right ts-forms">
                                                <div class="input append-small-btn" style="cursor:pointer;">
                                                    <div class="file-button" >
                                                        Elegir
                                                        <input type="file" name="file"  onchange="document.getElementById('append-small-btn').value = this.value;">
                                                    </div>
                                                    <input type="text" id="append-small-btn" placeholder="No hay archivo">
                                                </div>
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
            @foreach ($errors->all() as $error)
                Lobibox.notify('error', {
                    title: 'Error',
                    position: 'top right',
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: "{{ $error }}"
                });
            @endforeach
        </script>
    @endif

    <script>
    $(document).ready(function(){

        $(".btn-save").click(function(){
            if (!$("#form-control").valid()) 
                return

            $("#form-control").submit();
        })
    })

    </script>
@endpush