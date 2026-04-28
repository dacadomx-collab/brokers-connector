@extends('layouts.app')
@section('title','Lista de usuarios')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">


<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
@endpush

@section('breadcome')
    <li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
    <li><span class="bread-blod">Lista de usuarios</span></li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="contacts-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
            
                @foreach($users as $user)
                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" id="row-{{$user->id}}" style="padding-top: 20px;">
                    <div class="hpanel hblue contact-panel contact-panel-cs" style="min-height: 320px;">
                        <div class="panel-body custom-panel-jw">
                            {{-- <div class="social-media-in"> --}}
                               
                                {{-- <div class="col-md-1 col-lg-1 col-sm-1 col-xs-1 pull-right"> --}}
                                    

                                    
                                    @if ($user->id==auth()->user()->id || auth()->user()->hasRole("Admin"))

                                        <div style="position: absolute;right: -10px;top: -13px;">
                                            
                                            <a class="dropdown-toggle"  data-toggle="dropdown" href="#">
                                                <i class="fa fa-ellipsis-v fa-2x" style="padding: 10px;" aria-hidden="true"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right w-100" style="padding: 9px 0;" role="menu">
                                                <li><a href="{{route('users.showEdit',$user->id)}}" style="margin-top:0;">
                                                    <i class="fa fa-pencil fa-lg" style="color:#324a5e;" aria-hidden="true"></i>&nbsp; Editar</a></li>
                                                    @if($user->hasRole("Admin") || auth()->user()->id==$user->id)
                                                    
                                                    @else
                                                <li class="divider"></li>
                                                <li><a href="#" class="btn-delete" data-id="{{$user->id}}" style="margin-top:0;">
                                                    <i class="fa fa-times fa-lg" style="color:#F44336" aria-hidden="true"></i>&nbsp; Eliminar</a></li>
                                                    @endif
                                            </ul>
                                        </div>
                         
                                    @endif
                                    {{-- </div> --}}
                                    {{-- <a href="#" class="btn-delete" data-id="{{$user->id}}" ><i class="fa fa-trash"></i></a>
                                    <a href="{{route('users.showEdit',$user->id)}}" ><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a> --}}
                               
                               
                            {{-- </div> --}}
                            <div class="text-center">
                                    <img alt="logo" class="img-circle m-b" src="{{$user->foto_avatar}}" style="height:137px; margin-left: auto;
                                    margin-right: auto; display:block; width: 134px; object-fit: cover;">
                            </div>
                            <br>

                            <div class="text-center" style="color:#777;">
                                <h4><a style="font-size:20px;color:#3077b7;" href="#">{{ucwords($user->f_name)}}</a></h4>
                                <p class="all-pro-ad">{{$user->roles->pluck("display_name")->implode(", ")}}</p>

                                <div><span style="font-size:15px;" >{{$user->phone}}</span></div>
                                <div><strong>{{$user->email}}</strong></div>
                            </div>
                          
                                
                            
                        </div>
                        {{-- <div class="panel-footer contact-footer " style="width:100%;">
                            <div class="professor-stds-int " style="width:100%;">
                                <div class="professor-stds text-center" style="width:100%; ">
                                    <a href="{{route('users.profile', $user->id)}}" style="color:white;"> Ver perfil </a>
                                </div>
                            </div>
                        </div> --}}
                    </div>
                </div>
                @endforeach

            </div>
        </div>
    </div>
</div>





@endsection

@push('scripts')
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
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
    $(".btn-delete").click(function(){
       var id= $(this).data("id");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "Seguro desea eliminar a este usuario?",
            buttons: {
                yes: {
                    class: 'btn btn-default',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                }
            },
            callback: function ($nop, type, ev) 
            {
               if(type=="yes")
               {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                    $.ajax(
                    {   type:"post",
                        url: "{{ url('home/users/delete') }}",
                        data: {user_id: id},
                        success: function(result){
                            $("#row-"+id).html("");
                            Lobibox.notify('success', {
                                title: 'Exito',
                                position: 'top right',
                                showClass: 'fadeInDown',
                                hideClass: 'fadeUpDown',
                                msg: result
                            });
                        }
                    });
               }
            }
        });   
    });
</script>
@endpush
