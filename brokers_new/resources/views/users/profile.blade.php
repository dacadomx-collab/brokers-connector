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
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                    <div class="profile-info-inner">
                        <div class="profile-img">
                            @if($user->avatar!="")
                            <img alt="logo" class="img-circle m-b" src="{{$user->avatar}}" >
                            @else
                                <img alt="logo" class="img-circle m-b " src="{{ asset('img/profile/sin-avatar.png') }}" >
                            @endif
                        </div>
                        <div class="profile-details-hr">
                            <div class="row">
                                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                    <div class="address-hr">
                                        <p><b>Nombre</b><br /> {{$user->full_name." ".$user->last_name}}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                    <div class="address-hr tb-sm-res-d-n dps-tb-ntn">
                                        <p><b>Título</b><br /> {{$user->title}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                    <div class="address-hr">
                                        <p><b>Email</b><br /> {{$user->email}}</p>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-12 col-sm-12 col-xs-6">
                                    <div class="address-hr tb-sm-res-d-n dps-tb-ntn">
                                        <p><b>Teléfono</b><br /> {{$user->phone}}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="address-hr">
                                        <p><b>Tipo</b><br />{{$user->roles->pluck("display_name")->implode(",")}}</p>
                                    </div>
                                </div>
                            </div>
                            {{-- <div class="row">
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                                    <div class="address-hr">
                                        <a href="#"><i class="fa fa-facebook"></i></a>
                                        <h3>500</h3>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                                    <div class="address-hr">
                                        <a href="#"><i class="fa fa-twitter"></i></a>
                                        <h3>900</h3>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
                                    <div class="address-hr">
                                        <a href="#"><i class="fa fa-google-plus"></i></a>
                                        <h3>600</h3>
                                    </div>
                                </div>
                            </div> --}}
                        </div>
                    </div>
                </div>
                <div class="col-lg-8 col-md-8 col-sm-8 col-xs-12">
                    <div class="product-payment-inner-st res-mg-t-30 analysis-progrebar-ctn">
                        <ul id="myTabedu1" class="tab-review-design">
                            {{-- <li class="active"><a href="#description">Compañía</a></li> --}}
                            <li class="active"><a href="#reviews">Propiedades asignadas</a></li>
                            {{-- <li><a href="#INFORMATION">Update Details</a></li> --}}
                        </ul>
                        <div id="myTabContent" class="tab-content custom-product-edit">
                            <div class="product-tab-list tab-pane fade " id="description">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="">Nombre</label>
                                    </div>
                                    <div class="col-md-8">

                                    </div>
                                </div>  
                            </div>
                            <div class="product-tab-list tab-pane fade active in" id="reviews">
                                    <div class="single-review-st-item res-mg-t-30 table-mg-t-pro-n">
                                            <div class="single-review-st-hd">
                                                <h2>{{ $properties->count() }} Propiedades</h2>
                                            </div>
                                            @foreach($properties as $propieti)
                                            <a href="/properties/view/{{$propieti->id.'-'.str_slug($propieti->title)}}">
                                                <div class="single-review-st-text hoverq">
                                                  
                                                <img src="{{$propieti->image}}" alt="">
                                                    <div class="review-ctn-hf">
                                                        <h3>{{ $propieti->title }}</h3>
                                                        <p>{{ $propieti->description }}</p>
                                                    </div>
                                                </div>
                                            </a>   
                                            @endforeach
                                            @if($properties->count() > 10)
                                            <hr>
                                            <a href="{{ url('properties/index') }}">Ver más</a>
                                            @endif
                                        </div>
                                  
                            </div>
                            <div class="product-tab-list tab-pane fade" id="INFORMATION">
                                
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
        @if ($errors->any())
            @foreach ($errors->all() as $error)
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
        $("#btn-save").click(function(){
            if (!$("#form-control").valid()) 
                return

            $("#form-control").submit();
        })
    </script>
@endpush