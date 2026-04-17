@extends('layouts.app')
@section('title', ' Panel de control')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/modals.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
@endpush

@section('content')

    {{-- Alerta: empresa sin completar registro --}}
    @if(Auth::user()->company == null)
    <div class="analytics-sparkle-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 alert alert-danger" style="font-size:1.25rem;">
                    Para completar su registro, ingresa los datos de tu empresa.
                    <a href="{{ route('account') }}">Ir a tu cuenta</a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="library-book-area">
        <div class="container-fluid">
            <div class="row">

                {{-- Flip Cards: menú principal --}}
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    @include('includes.flip-cards')
                </div>

                {{-- Cuerpo del dashboard: mapa + sidebar --}}
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="dashboard-body">

                        <div class="dashboard-map">
                            @include('includes.map-properties')
                        </div>

                        <div class="dashboard-sidebar">
                            @include('includes.general-information')
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Modales --}}
    @include('includes.form-new-company')
    @include('includes.modal-no-pay')

@endsection

@push('scripts')
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#logo-preview').attr('src', e.target.result);
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#input-image").change(function() {
            readURL(this);
        });

        $("#phone").keypress(function(e) {
            if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
                return false;
            }
        });

        @if(Auth::user()->company == null && Auth::user()->hasRole('Admin'))
            $("#company_information").modal('show');
        @endif

        @if($company_suspended)
            $("#company_activate").modal('show');
        @endif

        @if (Session::has('success'))
            Lobibox.notify('success', {
                title: 'Información actualizada',
                position: 'top right',
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: "{{ session('success') }}"
            });
        @endif

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
@endpush
