<?php $__env->startSection('title','Mis facturas'); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('/')); ?>">Inicio</a> <span class="bread-slash">/</span></li>
<li><a href="<?php echo e(url('home/invoices')); ?>">Mis facturas</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Factura # <?php echo e($invoice->id); ?></span>
</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">




<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<div class="product-status mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-status-wrap drp-lst">
                    <h2>Factura # <?php echo e($invoice->id); ?></h2>
                        <div class="add-product">
                          
                                <a  class="btn btn-success" style="background-color: green;" disabled>Pagado</a>
                            
                           
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="product-status mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-status-wrap drp-lst">
                        <div class="row">
                            <div class="col-xs-6">
                                    <h4>Para:</h4>
                                    <p><?php echo e($company->owner_user->name); ?></p>
                                    <p><?php echo e($company->name); ?></p>
                                    <p><?php echo e($company->address); ?></p>
                                    <p><?php echo e($company->rfc); ?></p>
                                    
                            </div>
                            <div class="col-xs-6">
                                    <div class="text-right">
                                    <p>Fecha de pago: <?php echo e($invoice->m_invoice_date); ?></p>
                                            <p>Fecha de vencimiento: <?php echo e($invoice->m_due_date); ?></p>
                                            <p> <b> Estatus: </b>Pagado </p>
                                        </div>
                                </div>
                        </div>
                        <div class="row">

                            <div class="col-xs-12">
                                    <div class="asset-inner ">
                                        <table class="table table-hover">
                                            <tbody>
                                                <tr style="background-color:gray; ">
                                                    <th style="color:white;">Nombre</th>
                                                    <th style="color:white;">Cantidad</th>
                                                    <th style="text-align: right; color:white;">Precio</th>
                                                </tr>
                                                <tr>
                                                    <td><?php echo e($invoice->name); ?></td>
                                                    <td>1</td>
                                                    <td style="text-align: right;">$<?php echo e(number_format($invoice->cost_package)); ?> MXN</td>
                                                </tr>
                                                <?php if($invoice->users): ?>
                                                <tr>
                                                    <td>Usuario extra</td>
                                                    <td><?php echo e($invoice->users); ?></td>
                                                    <td style="text-align: right;">$<?php echo e(number_format($invoice->users * $invoice->cost_user)); ?> MXN</td>
                                                </tr>
                                                <?php endif; ?>
                                                  
                                            </tbody>
                                        </table>
                                        <hr >
                                        <p style="text-align: right;">Subtotal: $<?php echo e(($invoice->cost_package + ($invoice->cost_user * $invoice->users))); ?> MXN</p>
                                        <p style="text-align: right;">IVA 16%: $<?php echo e(($invoice->cost_package + ($invoice->cost_user * $invoice->users)) * 0.16); ?> MXN</p>
                                        <h3 style="text-align: right;">Total: $<?php echo e(number_format($invoice->total)); ?> MXN</h3>
                                         
                                        </div>
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



<?php if(Session::has('error_code')): ?>
<script>
        Lobibox.notify('error', {
            title: 'Error',
            verticalOffset: 5,
            position: 'top right',
            height: 'auto',      
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "<?php echo e(session('error_m')); ?>"
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
        msg: "EL PAGO SE REALIZÓ CON ÉXITO"
    });
  
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/invoices/view.blade.php ENDPATH**/ ?>