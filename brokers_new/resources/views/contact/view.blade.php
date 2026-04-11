@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">

<style>


.textoverflow {
  background-color: #ccc;
  height: 3px;
  vertical-align: middle;
  line-height: 1px;
  margin-top: 2em;
}
.textoverflow span { padding: 10px; background-color: #f6f8fa;}

.textoverflow.cntr { text-align: center;}



.p-15{
    padding: 15px;
}
p{
    overflow-wrap: break-word;
}    
h3{
    overflow-wrap: break-word;
}

@media (max-width: 768px) {
      .text-sm-center{
              text-align: center;
              padding-bottom: 3px;
       } 
    }

    input, textarea {
        outline: none;
        border: none;
        display: block;
        margin: 0;
        padding: 0;
        -webkit-font-smoothing: antialiased;
        font-family: "PT Sans", "Helvetica Neue", "Helvetica", "Roboto", "Arial", sans-serif;
        font-size: 1rem;
        color: #555f77;
      }
      input::-webkit-input-placeholder, textarea::-webkit-input-placeholder {
        color: #ced2db;
      }
      input::-moz-placeholder, textarea::-moz-placeholder {
        color: #ced2db;
      }
      input:-moz-placeholder, textarea:-moz-placeholder {
        color: #ced2db;
      }
      input:-ms-input-placeholder, textarea:-ms-input-placeholder {
        color: #ced2db;
      }
      
      p {
        line-height: 1.3125rem;
      }
      
      .comment-wrap {
        margin-bottom: 1.25rem;
        display: table;
        width: 100%;
        min-height: 5.3125rem;
      }
      
      .photo {
        padding-top: 0.625rem;
        display: table-cell;
        width: 3.5rem;
      }
      .photo .avatar {
        height: 2.25rem;
        width: 2.25rem;
        border-radius: 50%;
        background-size: contain;
      }
      
      .comment-block {
        padding: 1rem;
        background-color: #fff;
        display: table-cell;
        vertical-align: top;
        border-radius: 0.1875rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.08);
      }
      .comment-block textarea {
        width: 100%;
        resize: none;
      }
      
      .comment-text {
        margin-bottom: 0;
      }
      
      .bottom-comment {
        color: #acb4c2;
        font-size: 0.875rem;
      }
      

      .comment-name {
        float: left;
        font-size: 12px;
      }
      .comment-date {
        float: right;
        font-size: 12px;
      }
      
      .comment-actions {
        float: right;
      }
      .comment-actions li {
        display: inline;
        margin: -2px;
        cursor: pointer;
      }
      .comment-actions li.complain {
        padding-right: 0.75rem;
        border-right: 1px solid #e1e5eb;
      }
      .comment-actions li.reply {
        padding-left: 0.75rem;
        padding-right: 0.125rem;
      }
      .comment-actions li:hover {
        color: #0095ff;
      }
</style>
@endpush
@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
<li><a href="{{ route('contact.home') }}">Contactos</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">{{$contact->name}} {{ $contact->surname }}</span>
</li>
@endsection
@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="profile-info-inner ">
                    <div class="profile-details-hr ">
                        <div class="row">
                            <div class="col-md-9 text-sm-center">
                                <h3> {{$contact->name}} {{ $contact->surname }}</h3>
                            </div>
                            <div class="col-md-3">
                                <div class="row">
                                    {{-- @if ($agents->count())
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                        <div class="address-hr">
                                            <div class="dropdown">
                                                <button class="btn btn-default" type="button" data-toggle="dropdown"
                                                    style="height: 41px;">
                                                    <i class="fa fa-user"></i>
                                                    <span class="caret"></span></button>
                                                <ul class="dropdown-menu">
                                                    @foreach($agents as $agent)
                                                    <li id="{{ $agent->id }}"><a data-contact="{{$contact->id}}"
                                                            data-agent="{{ $agent->id }}" role="button"
                                                            class="btn-asignar"> Asignar {{ $agent->full_name }}
                                                        </a></li>
                                                    @endforeach
                                                </ul>

                                            </div>
                                        </div>
                                    </div>
                                    @endif --}}
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 ">
                                        <div class="address-hr">
                                            <a data-toggle="tooltip" data-placement="bottom"
                                                title="Enviar email a contacto" class="btn btn-default"
                                                href="mailto:{{$contact->email}}"><i class="fa  fa-envelope-o"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                        <div class="address-hr">
                                            <a data-toggle="tooltip" data-placement="bottom" title="Editar contacto"
                                                class="btn btn-default"
                                                href="{{ route('contact.edit', ['id'=>$contact->id]) }}"><i
                                                    class="fa fa-edit">
                                                </i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-3">
                                        <div class="address-hr">
                                            <a role="button" href="#" id="btn-delete" data-toggle="tooltip"
                                                data-placement="bottom" title="Eliminar contacto"
                                                class="btn btn-default" id="{{ $contact->id }}"
                                                data-id="{{ $contact->id }}"><i class="fa fa-times text-danger">
                                                </i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <!-- ====================================================================== -->
                        <!-- ============================ Etiquetas ===============================-->
                        <!--======================================================================== -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 ">
                                        <div class="p-15 text-center">
                                            <p> <i class='fa fa-fw fa-envelope'></i><b> Email</b><br /><a
                                                    href="mailto:{{$contact->email}}">{{$contact->email}}</a></p>
                                        </div>
                                    </div>
                                    {{-- <div class="col-md-3 col-sm-6">
                                        <div class="p-15 text-center">
                                            <p id="agente"><i class='fa fa-fw fa-user'></i><b> Agente asignado</b><br>
                                                @if($agents->count() == 0) Sin agente @endif
                                                @foreach($agents as $agent) @if( $contact->agent_id == $agent->id )
                                                {{$agent->full_name}} @endif @endforeach

                                            </p>
                                        </div>
                                    </div> --}}
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-15 text-center">
                                            <p><i class='fa fa-fw fa-bookmark'></i><b> Estatus</b><br>
                                                {{ config('app.contact_statuses')[$contact->status] }}

                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-15 text-center">
                                            <p><i class='fa fa-fw fa-user'></i><b> Tipo</b><br>
                                                Cliente

                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-15 text-center">
                                            <p><i class='fa fa-fw fa-chevron-right'></i><b> Origen</b><br>
                                                {{ config('app.contact_origins')[$contact->origin] }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 ">
                                        <div class=" tb-sm-res-d-n dps-tb-ntn p-15 text-center">
                                            <p><i class='fa fa-fw fa-phone'></i><b> Teléfonos</b><br />@foreach($phones as $phone) 
                                                <i class='fa fa-fw fa-mobile-phone'></i><a href="tel:{{ $phone->phone }}">{{ $phone->phone }}</a> <br>
                                                @endforeach </p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class=" tb-sm-res-d-n dps-tb-ntn p-15 text-center">
                                            <p><i class='fa fa-fw  fa-user-secret'></i><b> Puesto</b><br />
                                                {{$contact->m_job}}</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-15 text-center">
                                            <p><i class='fa fa-fw fa-map-marker'></i><b> Dirección</b><br>
                                                {{ $contact->m_address }}

                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="p-15 text-center">
                                            <p><i class='fa fa-fw fa-weixin'></i><b> Información adicional</b><br>
                                                {{ $contact->m_otros }}
                                            </p>
                                        </div>
                                    </div>
                                   
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Comentarios --}}
                <div class="row" style="margin-top: 32px;">
                    <div class="col-md-12">
                        <div class="comments">
                            <div class="comment-wrap">
                                <div class="photo">
                                    <div class="avatar" style="background-image: url('{{auth()->user()->foto_avatar}}')"></div>
                                </div>
                                <div class="comment-block">
                                    <form action="{{ url('home/contact-note/store') }}" method="POST" id="form">
                                        @csrf
                                        <input type="hidden" name="contact_id" value="{{$contact->id}}">
                                        <textarea id="textarea" maxlength="1000" name="content" id="" cols="30" rows="3"
                                            placeholder="Añade un comentario..."></textarea>
                                            <div class="bottom-comment">
                                                
                                                <ul class="comment-actions">
                                                    <li class="reply" id="send"><i class="fa fa-paper-plane"></i> Enviar</li>
                                                  
                                                </ul>
                                            </div>
                                        </form>
                                </div>
                            </div>

                            @foreach ($contact->comments as $comment)
                                
                            @switch($comment->type)
                                @case(1)
                                <div class="comment-wrap">
                                    <div class="photo">
                                        <div class="avatar"
                                            style="background-image: url('{{$comment->user->foto_avatar}}')">
                                        </div>
                                    </div>
                                    <div class="comment-block">
                                        <div style="color:#337ab7"><b>{{$comment->user->f_name}}</b></div>
                                        <p class="comment-text">{{$comment->content}}</p>
                                        <div class="bottom-comment">
                                            {{--  <div class="comment-name">{{$comment->user->f_name}}</div>  --}}
                                            <div class="comment-date">{{$comment->created_at->format('d-m-Y, h:m a')}}</div>
                                            
                                        </div>
                                    </div>
                                </div>
                                    @break
                                @case(2)

                                @php
                                    $property = App\Property::withTrashed()->find( json_decode($comment->content)->property_id );
                                @endphp

                                <div class="comment-wrap">
                                    
                                    <div class="comment-block">
                                        <div style="color:#555"><b>{{ucfirst($contact->name)}} {{ $contact->surname }} envio un mensaje</b></div>
                                        <p class="comment-text">{{json_decode($comment->content)->message}}
                                            <hr>                                          
                                            
                                        </p>
                                        <div class="row">
                                            <div class="col-md-3"><img style="width: 200px;" src="{{$property->image}}" alt=""></div>
                                            <div class="col-md-9">
                                                <h4><a href="{{ url('properties/view/'.$property->id) }}">{{$property->title}}</a></h4> 
                                              
                                                <p>{{$property->type_prop.' / '.$property->status_name}}</p>
                                                <p>${{ number_format($property->price,2).' '. $property->currency_attr}}</p>
                                            </div>
                                        </div>
                                        <div class="bottom-comment">
                                            {{--  <div class="comment-name">{{$comment->user->f_name}}</div>  --}}
                                            <div class="comment-date">{{$comment->created_at->format('d-m-Y, h:m a')}}</div>
                                            
                                        </div>
                                    </div>
                                    {{-- <div class="photo">
                                        <div class="avatar"
                                            style="background-image: url('https://capenetworks.com/static/images/testimonials/user-icon.svg'); float:right;">
                                        </div>
                                    </div> --}}
                                </div>
                                    @break
                                @default
                                    
                            @endswitch
                            @endforeach

                            <p class="textoverflow cntr"><span style="color:#878787;">Contacto creado a las {{$contact->created_at->format('d-m-Y H:i')}}</span></p>
                        </div>
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
<script>
    @if ($errors -> any())
        @foreach($errors -> all() as $error)
    Lobibox.notify('error', {
        title: 'Error',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "{{ $error }}"
    });
    @endforeach
    @endif
</script>
<script>
var btn = $(".btn-asignar");

    btn.click(function (e) {
        console.log(e);
        var contact = $(this).data("contact");
        var agent = $(this).data("agent");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "¿Seguro desea asignar el agente al contacto actual?",
            buttons: {
            yes: {
                class: 'btn btn-primary',
                text: 'Confirmar',
                closeOnClick: true
            },
            no: {
                class: 'btn btn-default',
                text: 'Cancelar',
                closeOnClick: true
            }
        },
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                    $.ajax(
                    {
                        type: "post",
                        url: "{{ route('contact.agent') }}",
                        data: { contact_id: contact, agent_id: agent, "_token": "{{ csrf_token()  }}" },
                        success: function (result) {
                                //btn.parents('li').hide();
                                Lobibox.notify('success', {
                                title: 'Exito',
                                position: 'top right',
                                showClass: 'fadeInDown',
                                hideClass: 'fadeUpDown',
                                msg: "Agente asignado"
                            });
                            var a = '<p id="agente"><i class="fa fa-fw   fa-user"></i><b>Agente asignado</b><br>'+result.agent_name+'</p>'
                            $("#agente").html(a);
                            $("#"+ agent).hide();
                            $("#"+ result.ant).show();
                        },
                        error:function(result)
                        {
                            alert("error");
                        }
                    });
                }
            }
        });
    });
</script>
<script>

    $('#send').click(function(){
        submitComment();
    });

    $('#textarea').keyup(function(event) {
        event.preventDefault();
        if (event.keyCode === 13) {
            // Do something
            submitComment();
        }
    });

    function submitComment(){

        const value = $('#form').children('textarea').val();
        if( value){
            var confirm = window.confirm("Esta seguro que desea enviar el comentario?");
            if (confirm == true) {
                $('#form').submit();
            } 
        }else{
            Lobibox.notify('warning', {
                title: 'Atención',
                position: 'top right',
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: "El comentario no se puede enviar vacío"
            });
        }
    }


    $("#btn-delete").click(function(){
       var id= $(this).data("id");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "Seguro desea eliminar a este contacto?",
            buttons: {
            yes: {
                class: 'btn btn-primary',
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
                    $.ajax(
                    {   type:"post",
                        url: "{{ route('contact.delete') }}", 
                        data: {user_id: id},
                        success: function(result){
                            $("#row-"+id).html("");
                
                            location.href ="/home/contact/home";
                        },
                        error:function(result){
                            alert("Ocurrio un error");
                        }
                    });
                }
            }
        });   
    });
</script>
@endpush