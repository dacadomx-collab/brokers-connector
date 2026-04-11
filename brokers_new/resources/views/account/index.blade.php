@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">

    <link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
@endpush

@section('title','Configuración de la cuenta')
@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Configuración de la cuenta</span>
</li>
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
                            <form action="{{route('update.company')}}" method="post" enctype="multipart/form-data" id="form-control">
                                @csrf
                                <input type="hidden" name="id" value="{{$company->id}}">
                                <div class="col-md-5">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <img id="preview-image" style="width: 18em;margin: 0 auto;height: 18em;display: block;border-radius: 5px;object-fit: cover;" 
                                            src="{{$company->logo ? $company->logo : 'https://www.creativosonline.org/blog/wp-content/plugins/accelerated-mobile-pages/images/SD-default-image.png'}}" alt="">
                                        </div>
                                        <div class="col-md-12 text-center" style="margin-top:20px">
                                            <label style="background: #303030;color: #fff;border: 1px solid #333;" class="btn"
                                                for="input-image">Subir imagen</label>
                                            <input type="file" name="file" id="input-image" accept="image/*" style="opacity:0; position:absolute;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="form-group @if($errors->has('name')) has-error @endif">
                                                <label for="">Nombre de la compañia</label>
                                                <input type="text" class="form-control" required maxlength="255"  name="name" id="name" placeholder="Nombre de la compañia"
                                                value="{{old('name', $company->name)}}" >
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group @if($errors->has('email')) has-error @endif">
                                                <label for="">Email de contacto <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="A esta dirección se enviaran todas las notificaiones del sistema incluyendo el contacto de tu página web"></i></label>
                                                <input type="email" class="form-control" required maxlength="255" email name="email" id="email" placeholder="Correo electroníco"
                                                    value="{{ old('email', $company->email) }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group @if($errors->has('phone')) has-error @endif">
                                                <label for="">RFC</label>
                                                <input type="text" class="form-control" required maxlength="20" name="rfc" id="phone"  placeholder="Teléfono"
                                                    value="{{old('rfc', $company->rfc)}}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group @if($errors->has('phone')) has-error @endif">
                                                <label for="">Telefono</label>
                                                <input type="text" class="form-control" required maxlength="20" name="phone" id="phone"  placeholder="Teléfono"
                                                    value="{{old('phone', $company->phone)}}">
                                            </div>
                                        </div>
                                        
                                        
                                        <div class="col-md-6">
                                            <div class="form-group @if($errors->has('address')) has-error @endif">
                                                <label for="">Dirección</label>
                                                <input type="address" class="form-control" required maxlength="255" name="address" id="address" placeholder="Dirección"
                                                    value="{{ old('address', $company->address)}}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group @if($errors->has('address')) has-error @endif">
                                                <label for="">Colonia</label>
                                                <input type="text" class="form-control" required maxlength="250" name="colony" placeholder="Dirección"
                                                    value="{{ old('colony', $company->colony)}}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group @if($errors->has('address')) has-error @endif">
                                                <label for="">C.P.</label>
                                                <input type="number" class="form-control" required maxlength="10" name="zipcode" placeholder="Dirección"
                                                    value="{{ old('zipcode', $company->zipcode)}}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{--  <div class="col-md-6">
                                    <div class="form-group edit-ta-resize">
                                            <label for="">Quienes Somos <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title='Este texto será visualizado en la seccion "Acerca de nosotros" en tu pagina web'></i></label> 
    
                                        <textarea name="about_us" rows="4" style="height:unset;" placeholder="Escribe acerca de tu organización"></textarea>
                                    </div>
                                    
                                </div>  --}}

                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="margin-top:35px;">
       
                                        <button type="button" id="btn-cancel" 
                                            class="btn btn-default">Cancelar &nbsp;<i
                                                class="fa fa-times-circle"></i></button>&nbsp;
                                        <button type="button" id="btn-save" 
                                            class="btn btn-primary waves-effect waves-light">Guardar &nbsp;<i
                                                class="fa fa-check-circle"></i></button>
                                    
                                </div>
                            </form>     
                        </div>
                    </div>
                </div>{{-- fin --}}
                
            </div>
            
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>

    <script>
        //visualizar imagen antes de ser subida
        function readURL(input) {
          if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
              $('#preview-image').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
          }
        }
        
        $("#input-image").change(function() {
          readURL(this);
        });
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
        $("#btn-save").click(function(){
            if (!$("#form-control").valid()) 
                return

            $("#form-control").submit();
        });

        document.getElementById('btn-cancel')
        .addEventListener('click', function(){
            window.location.href = '/home';
        })

    </script>
@endpush