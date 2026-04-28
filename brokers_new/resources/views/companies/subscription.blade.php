@extends('layouts.app')

@section('title', 'Suscripción — Brokers Connector')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
@endpush

@section('breadcome')
<li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Suscripción</span></li>
@endsection

@section('content')

<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">

            {{-- ── Selector de Plan ── --}}
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">
                    <h2 class="text-center">Elige tu plan</h2>
                    <p class="text-center text-muted">Plan actual: <strong>{{ $company->m_package->service ?? '—' }}</strong></p>
                </div>
            </div>

            {{-- ── Cards de plan ── --}}
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    @foreach ($services as $service)
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <label class="plan-card-label">
                            <input
                                type="radio"
                                name="selected_plan"
                                value="{{ $service->id }}"
                                class="plan-radio"
                                {{ $company->package == $service->id ? 'checked' : '' }}
                            >
                            <div class="panel panel-default plan-card {{ $company->package == $service->id ? 'plan-card-active' : '' }}">
                                <div class="panel-heading text-center">
                                    <strong>{{ $service->service }}</strong>
                                </div>
                                <div class="panel-body text-center">
                                    <h3 class="plan-price">${{ number_format($service->price, 0) }} <small>MXN / mes</small></h3>
                                    <ul class="list-unstyled plan-features">
                                        <li><i class="fa fa-check text-success"></i> {{ $service->users_included }} usuario(s) incluido(s)</li>
                                        @if ($service->user_price > 0)
                                        <li><i class="fa fa-plus-circle text-info"></i> ${{ number_format($service->user_price, 0) }} por usuario extra</li>
                                        @endif
                                        @if ($service->days_trial > 0)
                                        <li><i class="fa fa-clock-o text-warning"></i> {{ $service->days_trial }} días de prueba</li>
                                        @endif
                                        <li><i class="fa fa-check text-success"></i> Propiedades ilimitadas</li>
                                        <li><i class="fa fa-check text-success"></i> Soporte Lun–Dom 8:00–20:00</li>
                                    </ul>
                                </div>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── Formulario de Tarjeta (PCI Compliant) ── --}}
            <div class="col-lg-8 col-lg-offset-2 col-md-10 col-md-offset-1 col-sm-12 col-xs-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-lock"></i> Datos de Pago — Seguro con OpenPay
                        </h3>
                    </div>
                    <div class="panel-body">

                        <form action="{{ route('invoices.payment', ['invoice' => 0]) }}"
                              method="POST"
                              id="subscription-form">
                            @csrf
                            {{-- Token generado por OpenPay.js — único campo que llega al servidor --}}
                            <input type="hidden" name="token_id"                id="token_id">
                            <input type="hidden" name="selected_plan_id"        id="selected_plan_id">
                            <input type="hidden" name="deviceIdHiddenFieldName" id="deviceIdHiddenFieldName">

                            <div class="row">
                                {{-- Nombre del titular --}}
                                <div class="col-sm-12 form-group">
                                    <label for="sub-holder">Nombre del titular</label>
                                    <input id="sub-holder"
                                           type="text"
                                           class="form-control"
                                           placeholder="Como aparece en la tarjeta"
                                           maxlength="75"
                                           autocomplete="off"
                                           data-openpay-card="holder_name">
                                    {{-- SIN name="" — nunca viaja al servidor (PCI DSS) --}}
                                </div>

                                {{-- Número de tarjeta --}}
                                <div class="col-sm-12 form-group">
                                    <label for="sub-cardnumber">Número de tarjeta</label>
                                    <input id="sub-cardnumber"
                                           type="text"
                                           class="form-control"
                                           placeholder="0000 0000 0000 0000"
                                           autocomplete="off"
                                           data-openpay-card="card_number">
                                </div>

                                {{-- Vencimiento --}}
                                <div class="col-sm-4 form-group">
                                    <label>Mes de vencimiento</label>
                                    <input type="text"
                                           class="form-control"
                                           placeholder="MM"
                                           maxlength="2"
                                           data-openpay-card="expiration_month">
                                </div>
                                <div class="col-sm-4 form-group">
                                    <label>Año de vencimiento</label>
                                    <input type="text"
                                           class="form-control"
                                           placeholder="AA"
                                           maxlength="2"
                                           data-openpay-card="expiration_year">
                                </div>

                                {{-- CVV --}}
                                <div class="col-sm-4 form-group">
                                    <label for="sub-cvv">Código de seguridad</label>
                                    <input id="sub-cvv"
                                           type="text"
                                           class="form-control"
                                           placeholder="3 dígitos"
                                           maxlength="4"
                                           autocomplete="off"
                                           data-openpay-card="cvv2">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    <button type="button" id="btn-subscribe" class="btn btn-primary btn-lg">
                                        <i class="fa fa-lock"></i> Suscribirse de forma segura
                                    </button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-12 text-center">
                                    <small class="text-muted">
                                        <i class="fa fa-shield"></i>
                                        Transacción procesada por OpenPay.
                                        Tus datos de tarjeta nunca pasan por nuestros servidores.
                                    </small>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay.v1.min.js"></script>
<script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay-data.v1.min.js"></script>

<script>
$(document).ready(function () {

    OpenPay.setId("{{ env('OPENPAY_ID') }}");
    OpenPay.setApiKey("{{ env('OPENPAY_KEY_PUBLIC') }}");
    OpenPay.setSandboxMode({{ env('OPENPAY_PRODUCTION', false) ? 'false' : 'true' }});

    var deviceSessionId = OpenPay.deviceData.setup('subscription-form', 'deviceIdHiddenFieldName');

    // Sincroniza el plan seleccionado al hidden input antes de enviar
    $('input[name="selected_plan"]').on('change', function () {
        $('#selected_plan_id').val($(this).val());
    }).filter(':checked').trigger('change');

    $('#btn-subscribe').on('click', function (e) {
        e.preventDefault();

        if (!$('input[name="selected_plan"]:checked').length) {
            Lobibox.notify('warning', {
                title: 'Plan requerido',
                position: 'top right',
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: 'Por favor selecciona un plan antes de continuar.'
            });
            return;
        }

        $('#btn-subscribe').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
        OpenPay.token.extractFormAndCreate('subscription-form', onTokenSuccess, onTokenError);
    });

    function onTokenSuccess(response) {
        $('#token_id').val(response.data.id);
        $('#subscription-form').submit();
    }

    function onTokenError(response) {
        var desc  = response.data ? (response.data.description || response.message) : response.message;
        var error = 'Error al procesar la tarjeta.';

        var codes = {
            1001: 'Todos los campos de la tarjeta son requeridos.',
            2004: 'El número de tarjeta no es correcto.',
            2005: 'La fecha de vencimiento ya pasó.',
            2006: 'El código de seguridad es requerido.'
        };

        if (response.data && codes[response.data.error_code]) {
            error = codes[response.data.error_code];
        } else if (desc) {
            error = desc;
        }

        Lobibox.notify('error', {
            title: 'Error en la tarjeta',
            verticalOffset: 5,
            position: 'top right',
            height: 'auto',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: error
        });

        $('#btn-subscribe').prop('disabled', false).html('<i class="fa fa-lock"></i> Suscribirse de forma segura');
    }

});
</script>

@if (Session::has('error'))
<script>
    Lobibox.notify('error', {
        title: 'Error',
        verticalOffset: 5,
        position: 'top right',
        height: 'auto',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "{{ session('error') }}"
    });
</script>
@endif

@if (Session::has('success'))
<script>
    Lobibox.notify('success', {
        title: '¡Éxito!',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "{{ session('success') }}"
    });
</script>
@endif
@endpush
