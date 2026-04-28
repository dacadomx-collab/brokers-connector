<?php $__env->startSection('title','Facturas'); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('/')); ?>">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Mis facturas</span>

</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">


<link rel="stylesheet" href="<?php echo e(asset('css/card.css')); ?>">

<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="static-table-area">
  <div class="container-fluid">
      <div class="row">

        <?php if(auth()->user()->company->has_to_pay): ?>
        <div class="col-md-12">
            <div class="hpanel widget-int-shape responsive-mg-b-30">
                <div class="panel-body">
                    <div class="text-center content-box">
                        <h2 class="m-b-xs" style="margin-bottom: 15px;">Pago pendiente</h2>
                      
                        <div class="m icon-box" style="margin-bottom: 15px;">
                          <img src="/images/pay.svg" width="150" alt="">
                        </div>
                        <p class="small mg-t-box" style="margin: 0; color: #5d5d5d;">
                           Con tu pago nos ayudas a seguir creciendo, paga antes de que expire o bien si ya expiró tu servicio
                        </p>
                        <button class="btn btn-success btn-md widget-btn-1 btn-sm mg-t-box" style="font-size: large;
                        padding: 12px 18px;" data-toggle="modal" data-target="#PrimaryModalalert">Pagar ahora</button>
                    </div>
                </div>
            </div>
            <hr>
        </div>
        <?php endif; ?>

          <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
              <div class="sparkline8-list">
                  <div class="sparkline8-hd">
                      <div class="main-sparkline8-hd">
                          <h1></h1>
                      </div>
                  </div>
                  <div class="sparkline8-graph">
                     
                      <div class="static-table-list table-responsive">
                          <table class="table table-bordered">
                              <thead>
                                  <tr>
                                      <th>Factura #</th>
                                      <th>Total</th>
                                      <th>Status</th>
                                      <th>Fecha de pago</th>
                                      <th>Fecha de vencimiento</th>
                                      <th>Detalles</th>
                                  </tr>
                              </thead>
                              <tbody>


                           
                                  <?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><a href="<?php echo e(url('home/invoices/'.$invoice->id)); ?>"><?php echo e($invoice->id); ?></a></td>
                                        <td>$ <?php echo e($invoice->total); ?> MXN</td>
                                        <td> <?php if($invoice->status): ?>  <button class="btn btn-success btn-xs">Pagado </button> <?php else: ?> <button class="btn btn-warning btn-xs">Pendiente de pago</button>  <?php endif; ?></td>
                                        <td><?php echo e($invoice->payday->format('d-m-Y')); ?></td>
                                        <td><?php echo e($invoice->m_due_date); ?></td>
                                        <td> <a href="<?php echo e(url('home/invoices/'.$invoice->id)); ?>" class="btn btn-default">Ver factura</a> </td>
                                    </tr>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                              </tbody>
                          </table>
                          <div align="center">
                            <?php echo e($invoices->links()); ?>

                        </div>
                      </div>
                  </div>
              </div>
          </div>
   
      </div>

  </div>
</div>


<?php echo $__env->make('invoices.payment_method.card', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/3.4.0/imask.min.js"></script>
    <script src="<?php echo e(asset('js/card.js')); ?>"></script>
    <script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay.v1.min.js"></script>
    <script type='text/javascript' src="https://openpay.s3.amazonaws.com/openpay-data.v1.min.js"></script>  

    <script>
        $(document).ready(function() {

            OpenPay.setId("<?php echo e(env('OPENPAY_ID')); ?>");
            OpenPay.setApiKey("<?php echo e(env('OPENPAY_KEY_PUBLIC')); ?>");
            OpenPay.setSandboxMode(<?php echo e(env('OPENPAY_PRODUCTION', false) ? 'false' : 'true'); ?>);
            //Se genera el id de dispositivo
            var deviceSessionId = OpenPay.deviceData.setup("payment-form", "deviceIdHiddenFieldName");
            
            $('#pay-button').on('click', function(event) {
                event.preventDefault();
                $("#pay-button").prop("disabled", true);
                OpenPay.token.extractFormAndCreate('payment-form', sucess_callbak, error_callbak);                
            });
        
            var sucess_callbak = function(response) {
            var token_id = response.data.id;
            $('#token_id').val(token_id);
            $('#payment-form').submit();
            };
        
            var error_callbak = function(response) {
                var desc = response.data.description != undefined ? response.data.description : response.message;
                //alert(response.data.error_code);
                error = "Error desconocido."
                if(response.data.error_code== 1001)
                {
                    if('cvv2 length must be 3 digits' == desc)
                    {
                        error = "El codigo de seguridad no es correcto.";
                    }else if( "card_number length is invalid" == desc)
                    {
                        error = "El numero de tarjeta no es valido."
                    }else{
                        error = "Todos los campos son necesarios."
                    }
                
                }
                else if(response.data.error_code == 2005)
                {
                    error ="La fecha de vencimiento ya paso.";
                }
        
                else if(response.data.error_code== 2006)
                {
                    error ="El codigo de seguridad es requerido.";
                }
                else if(response.data.error_code== 2004)
                {
                    error ="El numero de tarjeta no es correcto.";
                }
                else{
                    error ="ERROR " + desc;
                }
                Lobibox.notify('error', {
                    title: 'Error',
                    verticalOffset: 5,
                    position: 'top right',
                    height: 'auto',      
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: error
            });
                $("#pay-button").prop("disabled", false);
            };
        
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
            title: 'Información actualizada',
            position: 'top right',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "<?php echo e(session('success')); ?>"
        });
    </script>
    <?php endif; ?>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/invoices/index.blade.php ENDPATH**/ ?>