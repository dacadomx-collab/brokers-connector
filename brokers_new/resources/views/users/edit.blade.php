@extends('layouts.app')
@section('title','Editar usuario '.$user->f_name)

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
<li><span class="bread-blod">Editar usuario {{$user->f_name}}</span></li>
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
                            <form action="{{route('update.users')}}" method="post" enctype="multipart/form-data" id="form-control">
                                <input type="hidden" name="id" value="{{$user->id}}">
                                @csrf
                                @include("users.utils.form",["data"=>$user, "edit"=>true])
                                
                            </form>
                        </div>
                    </div>
                </div>{{--   fin --}}
            </div>
        </div>
    </div>
</div>

@include('users.utils.modal-avatar',["data"=>$user])
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
<script>
 $('.phone-input').keypress( function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {

        return false;
        }
    });
</script>
@if (Session::has('success'))
<script>
    Lobibox.notify('success', {
        title: 'Exito',
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
// $('.imagen').change(function (e) {
       
//     if (this.files && this.files[0]) {
//         var reader = new FileReader();
//         var url= $(this).val();
//         console.log(url)
//         reader.onload = function (e) {
//             $('#avatar').attr('src', e.target.result);
           
//         }

//         reader.readAsDataURL(this.files[0]);
//     }
// });

$('.phone-input').keypress( function (e) {
    
    if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
        return false;
    }

});

// $("#btn-save-avatar").click(function(){
//     $("#form-control").submit();
// })

$("#btn-save").click(function(){
    if ($("#form-control").valid()) 
    $("#form-control").submit();
      
})
</script>
@endpush
