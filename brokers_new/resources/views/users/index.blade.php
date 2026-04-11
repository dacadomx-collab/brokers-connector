@extends('layouts.app')
@section('title','Agregar usuario')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/modals.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">

<style>

.container-image {
  position: relative;
  width: 100%;
  height: 250px;
  box-shadow: 2px 9px 20px;
  cursor: pointer;
  margin-bottom: 25px;
}

.image {
    display: block;
    width: 100%;
    height: -webkit-fill-available;
    object-fit: cover;
  }

.overlay {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  height: 100%;
  width: 100%;
  opacity: 0;
  transition: .3s ease;
  background-color: rgba(0, 0, 0, .5);

}

.container-image:hover .overlay {
  opacity: 1;
}

.text {
  color: white;
  font-size: -webkit-xxx-large;
  position: absolute;
  top: 50%;
  left: 50%;
  -webkit-transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  text-align: center;
}

</style>
@endpush

@section('breadcome')
    <li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
    <li><span class="bread-blod">Agregar usuario</span></li>
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
                            <form action="{{route('create.users')}}" method="post" enctype="multipart/form-data" id="form-control">
                                @csrf
                                @include("users.utils.form",["data"=>$user,"edit"=>false])
                            </form>
                        </div>
                    </div>
                </div>{{--   fin --}}
            </div>
        </div>
    </div>
</div>

{{-- @include('users.utils.modal-avatar',["data"=>$user ]) --}}

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

    $('.phone-input').keypress( function (e) {
        
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }

    });

    // $("#btn-save-avatar").click(function(){
    //     
    // })
    $("#form-control").validate({
    rules: {
            pass: {
                minlength: 8
            },
            password_confirmation: {
                minlength: 8,
                equalTo: "#pass"
            }
        },
        messages: {
        pass: {
            required: "Este campo es requerido",
            minlength: "La contraseña debe tener al menos 8 caracteres",
        },
        password_confirmation: {
            required: "Este campo es requerido",
            minlength: "La contraseña debe tener al menos 8 caracteres",
            equalTo: "Las contraseñas no coinciden"
        }
    }
})
    $("#btn-save").click(function(){
         if ($("#form-control").valid()) 
             return $("#form-control").submit();
            // console.log();
            
       
    })

    

</script>
@if (Session::has('error'))
<script>
    Lobibox.notify('error', {
        title: 'Aviso!',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "{{ session('error') }}"
    });

</script>
@endif
@endpush
