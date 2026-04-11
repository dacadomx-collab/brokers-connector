@extends('layouts.app')
@section('title','Lista de contactos')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<style>
    .container {
        position: relative;

            {
                {
                --height: 14rem;
                --
            }
        }

            {
                {
                -- border: 1px solid;
                --
            }
        }
    }

    .jumbob {
        position: absolute;
        top: 50%;
        left: 50%;
        background: transparent;
        transform: translate(-50%, -50%);
        font-size: 18px !important;
        font-weight: inherit !important;
    }

    a i:hover {
        color: #000;
    }
</style>
@endpush

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Lista de contactos</span></li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="contacts-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <div class="sparkline8-list">
                   
                        <div class="static-table-list table-responsive">
                                <a class="btn btn-default pull-right" href="{{ route('create.contact') }}" >Agregar contacto</a>
                            <table class="table table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Tipo</th>
                                        <th>Estatus</th>
                                        <th>Origen</th>
                                        <th>Creado</th>
                                        <th></th>
                                        <th></th>

                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach($contacts as $contact)
                                    <tr id="tr-{{$contact->id}}">
                                        <td><a data-toggle="tooltip" data-placement="top" title="Ver perfil"
                                                href="{{ route('contact.show', ['id' => $contact->id])}}">{{ $contact->name }}
                                                {{$contact->surname}}</a></td>
                                        <td>{{ $contact->email  }}</td>

                                        @if ($contact->type)
                                        <td>{{config('app.contact_types')[$contact->type]}}</td>
                                        @else
                                        <td>No especificado</td>
                                        @endif

                                       @if ($contact->status)
                                       <td>{{config('app.contact_statuses')[$contact->status]}}</td>
                                       @else
                                       <td>No especificado</td>
                                       @endif

                                       @if ($contact->origin)
                                       <td>{{config('app.contact_origins')[$contact->origin]}}</td>
                                       @else
                                       <td>No especificado</td>
                                       @endif
                                       
                                        <td>{{$contact->m_created}}</td>
                                        <td style="display:inline-flex"><a data-toggle="tooltip" data-placement="top"
                                                title="Editar contacto"
                                                href="{{ route('contact.edit', ['id'=>$contact->id]) }}" class="">
                                                <i style="font-size: x-large" class="fa fa-fw fa-edit"></i></a>
                                            <button data-toggle="tooltip" class="btn-delete" data-placement="top"
                                                title="Eliminar contacto" id="{{ $contact->id }}"
                                                data-id="{{ $contact->id }}"
                                                style="background: transparent;border: unset;font-size: x-large;color: #e12503;transform: translateY(-3.5px);"><i
                                                    class="fa fa-fw fa-times"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>


                        </div>
                    </div>
                        @if ($contacts->count()<=0) 
                        <div class=" h-100 d-flex justify-content-center align-items-center" style="
                        padding-top: 150px;
                    ">
                            <div class="container theme-showcase" role="main">

                                <div class="jumbob">
                                    <h2 style="">Aún no tienes contactos <i class="fa fa-sad-tear"></i></h2>
                                    <p>Intenta agregar algunos</p>
                                    <a href="{{ route('create.contact') }}" class="btn btn-default">AGREGAR</a>
                                </div>

                            </div>

                            @endif
                            <div align="center">
                                {{ $contacts->links() }}
                            </div>
                   
                </div>

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
        msg: "{{ session('success') }}",
        

    });

</script>
@endif

@if ($errors->any())

<script>
    @foreach($errors -> all() as $error)
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
    $(".btn-delete").click(function () {
        var id = $(this).data("id");
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
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                    $.ajax(
                        {
                            type: "post",
                            url: "/home/contact/delete",
                            data: { user_id: id },
                            success: function (result) {
                                $("#tr-" + id).html("");
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