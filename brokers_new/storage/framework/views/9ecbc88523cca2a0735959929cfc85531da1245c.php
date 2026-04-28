<?php $__env->startSection('title', 'Suscripción — Brokers Connector'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('home')); ?>">Inicio</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Suscripción</span></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">

        
        <div class="row">
            <div class="col-xs-12">
                <h2 class="checkout-section-title">Elige tu plan</h2>
                <p class="checkout-section-subtitle">
                    Plan actual: <strong><?php echo e($company->m_package->service ?? '—'); ?></strong>
                </p>
            </div>
        </div>

        
        <div class="row">

            
            <div class="col-md-7 col-sm-12 col-xs-12">

                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <label class="plan-card-label">

                    <input type="radio"
                           name="selected_plan"
                           value="<?php echo e($service->id); ?>"
                           class="plan-radio"
                           <?php echo e($company->package == $service->id ? 'checked' : ''); ?>>

                    <div class="plan-card <?php echo e($company->package == $service->id ? 'plan-card-active' : ''); ?>">

                        <div class="panel-heading">
                            <span><?php echo e($service->service); ?></span>
                            <?php if($company->package == $service->id): ?>
                                <span class="pull-right label label-primary">Plan actual</span>
                            <?php endif; ?>
                        </div>

                        <div class="panel-body">
                            <div class="row">

                                <div class="col-xs-5 text-center">
                                    <p class="plan-price">
                                        $<?php echo e(number_format($service->price, 0)); ?>

                                        <small>MXN<br>/ mes</small>
                                    </p>
                                </div>

                                <div class="col-xs-7">
                                    <ul class="plan-features list-unstyled">
                                        <li>
                                            <i class="fa fa-users text-primary" aria-hidden="true"></i>
                                            <?php echo e($service->users_included); ?> usuario(s) incluido(s)
                                        </li>
                                        <?php if($service->user_price > 0): ?>
                                        <li>
                                            <i class="fa fa-plus-circle text-info" aria-hidden="true"></i>
                                            $<?php echo e(number_format($service->user_price, 0)); ?> por usuario extra
                                        </li>
                                        <?php endif; ?>
                                        <?php if($service->days_trial > 0): ?>
                                        <li>
                                            <i class="fa fa-gift text-warning" aria-hidden="true"></i>
                                            <?php echo e($service->days_trial); ?> días de prueba gratis
                                        </li>
                                        <?php endif; ?>
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
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </div>

            
            <div class="col-md-5 col-sm-12 col-xs-12">
                <div class="checkout-sticky">
                    <div class="checkout-panel">

                        <p class="checkout-panel-title">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            Pago seguro con OpenPay
                        </p>

                        <form action="<?php echo e(route('subscription.process')); ?>"
                              method="POST"
                              id="subscription-form">
                            <?php echo csrf_field(); ?>

                            
                            <input type="hidden" name="token_id"                id="token_id">
                            <input type="hidden" name="selected_plan_id"        id="selected_plan_id">
                            <input type="hidden" name="deviceIdHiddenFieldName" id="deviceIdHiddenFieldName">

                            
                            <div class="form-group">
                                <label class="payment-label" for="sub-holder">Nombre del titular</label>
                                <input id="sub-holder"
                                       type="text"
                                       class="payment-input"
                                       placeholder="Como aparece en la tarjeta"
                                       maxlength="75"
                                       autocomplete="off"
                                       data-openpay-card="holder_name">
                                
                            </div>

                            
                            <div class="form-group">
                                <label class="payment-label" for="sub-cardnumber">Número de tarjeta</label>
                                <input id="sub-cardnumber"
                                       type="text"
                                       class="payment-input"
                                       placeholder="0000  0000  0000  0000"
                                       autocomplete="off"
                                       data-openpay-card="card_number">
                            </div>

                            
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

                        <div class="checkout-trust-badge">
                            <i class="fa fa-shield" aria-hidden="true"></i>
                            Transacción cifrada por OpenPay.
                            Tus datos de tarjeta <strong>nunca</strong> pasan por nuestros servidores.
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
<script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay.v1.min.js"></script>
<script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay-data.v1.min.js"></script>

<script>
$(document).ready(function () {

    /* ── OpenPay init ─────────────────────────────── */
    OpenPay.setId("<?php echo e(env('OPENPAY_ID')); ?>");
    OpenPay.setApiKey("<?php echo e(env('OPENPAY_KEY_PUBLIC')); ?>");
    OpenPay.setSandboxMode(<?php echo e(env('OPENPAY_PRODUCTION', false) ? 'false' : 'true'); ?>);
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

<?php if(Session::has('error')): ?>
<script>
    Lobibox.notify('error', {
        title: 'Error',
        verticalOffset: 5,
        position: 'top right',
        height: 'auto',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "<?php echo e(session('error')); ?>"
    });
</script>
<?php endif; ?>

<?php if(Session::has('success')): ?>
<script>
    Lobibox.notify('success', {
        title: '¡Éxito!',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "<?php echo e(session('success')); ?>"
    });
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/companies/subscription.blade.php ENDPATH**/ ?>