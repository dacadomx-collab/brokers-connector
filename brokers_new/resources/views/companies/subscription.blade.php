@extends('layouts.app')

@section('title', 'Suscripción — Brokers Connector')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
@endpush

@section('breadcome')
<li><a href="{{ url('home') }}">Inicio</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Suscripción</span></li>
@endsection

@section('content')

<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">

        {{-- ── Cabecera ── --}}
        <div class="row">
            <div class="col-xs-12">
                <h2 class="checkout-section-title">Elige tu plan</h2>
                <p class="checkout-section-subtitle">
                    Plan actual: <strong>{{ $company->m_package->service ?? '—' }}</strong>
                </p>
            </div>
        </div>

        {{-- ── Layout principal: planes izquierda · formulario derecha ── --}}
        <div class="row">

            {{-- Columna de planes — col-md-7, apila en móvil --}}
            <div class="col-md-7 col-sm-12 col-xs-12">

                @foreach ($services as $service)
                <label class="plan-card-label">

                    <input type="radio"
                           name="selected_plan"
                           value="{{ $service->id }}"
                           class="plan-radio"
                           {{ $company->package == $service->id ? 'checked' : '' }}>

                    <div class="plan-card {{ $company->package == $service->id ? 'plan-card-active' : '' }}">

                        <div class="panel-heading">
                            <span>{{ $service->service }}</span>
                            @if ($company->package == $service->id)
                                <span class="pull-right label label-primary">Plan actual</span>
                            @endif
                        </div>

                        <div class="panel-body">
                            <div class="row">

                                <div class="col-xs-5 text-center">
                                    <p class="plan-price">
                                        ${{ number_format($service->price, 0) }}
                                        <small>MXN<br>/ mes</small>
                                    </p>
                                </div>

                                <div class="col-xs-7">
                                    <ul class="plan-features list-unstyled">
                                        <li>
                                            <i class="fa fa-users text-primary" aria-hidden="true"></i>
                                            {{ $service->users_included }} usuario(s) incluido(s)
                                        </li>
                                        @if ($service->user_price > 0)
                                        <li>
                                            <i class="fa fa-plus-circle text-info" aria-hidden="true"></i>
                                            ${{ number_format($service->user_price, 0) }} por usuario extra
                                        </li>
                                        @endif
                                        @if ($service->days_trial > 0)
                                        <li>
                                            <i class="fa fa-gift text-warning" aria-hidden="true"></i>
                                            {{ $service->days_trial }} días de prueba gratis
                                        </li>
                                        @endif
                                        <li>
                                            <i class="fa fa-home text-success" aria-hidden="true"></i>
                                            Propiedades ilimitadas
                                        </li>
                                        <li>
                                            <i class="fa fa-headphones text-success" aria-hidden="true"></i>
                                            Soporte Lun–Dom 8:00–20:00
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </div>

                    </div>
                </label>
                @endforeach

            </div>{{-- /col planes --}}

            {{-- Columna del formulario — col-md-5, sticky en desktop --}}
            <div class="col-md-5 col-sm-12 col-xs-12">
                <div class="checkout-sticky">
                    <div class="checkout-panel">

                        <p class="checkout-panel-title">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            Pago seguro con OpenPay
                        </p>

                        <form action="{{ route('subscription.process') }}"
                              method="POST"
                              id="subscription-form">
                            @csrf

                            {{-- Campos ocultos — únicos que viajan al servidor --}}
                            <input type="hidden" name="token_id"                id="token_id">
                            <input type="hidden" name="selected_plan_id"        id="selected_plan_id">
                            <input type="hidden" name="deviceIdHiddenFieldName" id="deviceIdHiddenFieldName">

                            {{-- Nombre del titular --}}
                            <div class="form-group">
                                <label class="payment-label" for="sub-holder">Nombre del titular</label>
                                <input id="sub-holder"
                                       type="text"
                                       class="payment-input"
                                       placeholder="Como aparece en la tarjeta"
                                       maxlength="75"
                                       autocomplete="off"
                                       data-openpay-card="holder_name">
                                {{-- SIN name="" — nunca viaja al servidor (PCI DSS) --}}
                            </div>

                            {{-- Número de tarjeta --}}
                            <div class="form-group">
                                <label class="payment-label" for="sub-cardnumber">
                                    Número de tarjeta
                                    <span class="pull-right">
                                        {{-- Estilo inline autorizado: colores de marca oficiales Visa / MC / Amex --}}
                                        <i class="fa fa-cc-visa" style="color: #1a1f71; font-size: 24px; margin-right: 5px;" aria-label="Visa"></i>
                                        <i class="fa fa-cc-mastercard" style="color: #eb001b; font-size: 24px; margin-right: 5px;" aria-label="Mastercard"></i>
                                        <i class="fa fa-cc-amex" style="color: #2e77bc; font-size: 24px;" aria-label="American Express"></i>
                                    </span>
                                </label>
                                <input id="sub-cardnumber"
                                       type="text"
                                       class="payment-input"
                                       placeholder="0000  0000  0000  0000"
                                       autocomplete="off"
                                       data-openpay-card="card_number">
                            </div>

                            {{-- Vencimiento + CVV en la misma fila --}}
                            <div class="payment-row">
                                <div class="payment-field">
                                    <label class="payment-label">Mes</label>
                                    <input type="text"
                                           class="payment-input"
                                           placeholder="MM"
                                           maxlength="2"
                                           data-openpay-card="expiration_month">
                                </div>
                                <div class="payment-field">
                                    <label class="payment-label">Año</label>
                                    <input type="text"
                                           class="payment-input"
                                           placeholder="AA"
                                           maxlength="2"
                                           data-openpay-card="expiration_year">
                                </div>
                                <div class="payment-field">
                                    <label class="payment-label" for="sub-cvv">CVV</label>
                                    <input id="sub-cvv"
                                           type="text"
                                           class="payment-input"
                                           placeholder="···"
                                           maxlength="4"
                                           autocomplete="off"
                                           data-openpay-card="cvv2">
                                </div>
                            </div>

                            <button type="button" id="btn-subscribe" class="btn-secure-pay">
                                <i class="fa fa-lock" aria-hidden="true"></i>
                                Suscribirse de forma segura
                            </button>

                        </form>

                        <div class="text-center" style="margin-top: 15px;">
                            <img src="https://img.openpay.mx/assets/paynet_logo.png"
                                 alt="OpenPay"
                                 height="30"
                                 style="opacity: 0.8;">
                            <p style="font-size: 11px; color: #777; margin-top: 5px;">
                                <i class="fa fa-lock text-success"></i>
                                Sus datos están encriptados y se transmiten de forma segura.
                                No almacenamos los números de su tarjeta.
                            </p>
                        </div>

                    </div>
                </div>
            </div>{{-- /col formulario --}}

        </div>{{-- /row principal --}}
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay.v1.min.js"></script>
<script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay-data.v1.min.js"></script>

<script>
$(document).ready(function () {

    /* ── OpenPay init ─────────────────────────────── */
    OpenPay.setId("{{ env('OPENPAY_ID') }}");
    OpenPay.setApiKey("{{ env('OPENPAY_KEY_PUBLIC') }}");
    OpenPay.setSandboxMode({{ env('OPENPAY_PRODUCTION', false) ? 'false' : 'true' }});
    OpenPay.deviceData.setup('subscription-form', 'deviceIdHiddenFieldName');

    /* ── Selector de plan — sincroniza hidden + estado visual ─── */
    $('input[name="selected_plan"]').on('change', function () {
        $('#selected_plan_id').val($(this).val());
        // Actualiza la clase activa sin recargar página
        $('.plan-card').removeClass('plan-card-active');
        $(this).closest('.plan-card-label').find('.plan-card').addClass('plan-card-active');
    }).filter(':checked').trigger('change');

    /* ── Submit ──────────────────────────────────────────────── */
    $('#btn-subscribe').on('click', function () {

        if (!$('input[name="selected_plan"]:checked').length) {
            Lobibox.notify('warning', {
                title: 'Plan requerido',
                position: 'top right',
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: 'Selecciona un plan antes de continuar.'
            });
            return;
        }

        $(this).prop('disabled', true)
               .html('<i class="fa fa-spinner fa-spin"></i>  Procesando...');

        // Eliminar espacios antes de enviar — OpenPay exige solo dígitos en card_number
        var cardInput = document.querySelector('[data-openpay-card="card_number"]');
        if (cardInput) {
            cardInput.value = cardInput.value.replace(/\s+/g, '');
        }

        OpenPay.token.extractFormAndCreate('subscription-form', onSuccess, onError);
    });

    function onSuccess(response) {
        $('#token_id').val(response.data.id);
        $('#subscription-form').submit();
    }

    function onError(response) {
        var codes = {
            1001: 'Todos los campos de la tarjeta son requeridos.',
            2004: 'El número de tarjeta no es correcto.',
            2005: 'La fecha de vencimiento ya pasó.',
            2006: 'El código de seguridad es requerido.'
        };

        var code  = response.data ? response.data.error_code : null;
        var desc  = response.data ? response.data.description : response.message;
        var error = codes[code] || desc || 'Error al procesar la tarjeta.';

        Lobibox.notify('error', {
            title: 'Error en la tarjeta',
            verticalOffset: 5,
            position: 'top right',
            height: 'auto',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: error
        });

        $('#btn-subscribe').prop('disabled', false)
                          .html('<i class="fa fa-lock"></i>  Suscribirse de forma segura');
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
