<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/form/all-type-forms.css')); ?>">
<style>



/**
  Component
**/

label {
    width: 100%;
}

.card-input-element {
    display: none;
}

.card-input {
    margin: 10px;
    padding: 00px;
}

.card-input:hover {
	
	color: #3a7999;
    box-shadow: 0 0 1px 1px #2ecc71;
    cursor: pointer;
    
}

.card-input-element:checked + .card-input {
     box-shadow: 0 0 1px 1px #2ecc71;
 }

.divul
{
    font-weight: 500;

}
.divul li
{
    padding: 5px;
}


</style>

<?php $__env->stopPush(); ?>

<?php $__env->startSection('title', 'Planes disponibles'); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('/')); ?>">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Planes disponibles</span>
</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Single pro tab review Start-->
<div>

</div>



<div class="product-status mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-status-wrap drp-lst">
                <h2>Selecciona el plan deseado</h2>
                <p>Plan actual: <?php echo e($company->m_package->service); ?></p>
                    <div class="add-product">
                    <form action="<?php echo e(route('edit.plans')); ?>" method="POST" id="form-plan">
                        <?php echo csrf_field(); ?>
                        <div class="row">
                        <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($service->id != 4): ?><!-- 4 es el usuario extra en la base de datos-->
                            <div class="col-md-4 col-lg-4 col-sm-4">
        
                                <label>
                                  <input type="radio" name="package" value="<?php echo e($service->id); ?>" class="card-input-element" required/>
                        
                                    <div class="panel panel-default card-input">
                                    <div class="panel-heading">Paquete <?php echo e($service->id); ?></div>
                                      <div class="panel-body" align="center">
                                        <h3 ><?php echo e($service->service); ?></h3>
                                        <h4 style="color:darkgray">$ <?php echo e($service->price); ?> MXN + IVA</h4>
                                      </div>
                                      <div class="panel-body " align="center">
                                          <?php if($service->id == 1): ?>
                                          <img src="https://image.flaticon.com/icons/svg/1256/1256652.svg " alt=""><br>
                                          <br>
                                          <ul class="divul">
                                            <li>  Usuario único</li>
                                            <li>
                                                Propiedades Ilimitadas
                                            </li>
                                            <li>
                                                Contactos Ilimitados
                                            </li>
                                            <li>
                                                Correo Electrónico
                                            </li>
                                            <li>
                                                Hosting Inlcuido
                                            </li>
                                            <li>
                                                Página Web
                                            </li>
                                           
                                       
                                         <li>
                                            Soporte Técnico Lun-Dom 8:00 - 20:00
                                        </li>
                                          </ul>
                                         
                                            
 
                                          <?php endif; ?>
                                          <?php if($service->id == 2): ?>
                                          <img src="https://image.flaticon.com/icons/svg/1256/1256646.svg" alt=""><br>
                                          <br>
                                          <ul class="divul">
                                            <li>
                                                $50 Por Usuario Extra
                                            </li>
                                            <li>
                                                Propiedades Ilimitadas
                                            </li>
                                            <li>
                                                Correo Electrónico
                                            </li>
                                            <li>
                                                Hosting Inlcuido
                                            </li>
                                            <li>
                                                Página Web
                                            </li>
                                            <li>
                                                Contactos Ilimitados
                                            </li>
                          
                                            <li>
                                                Soporte Técnico Lun-Dom 8:00 - 20:00
                                            </li>
                                            
                                          </ul>
                              

                                          <?php endif; ?>
                                          <?php if($service->id == 3): ?>
                                        <img  src="https://image.flaticon.com/icons/svg/1256/1256677.svg" alt=""><br>
                                        <br>
                                        <ul class="divul">
                                            <li>
                                                $100 Por Usuario Extra
                                            </li>
                                            <li>
                                                Propiedades Ilimitadas
                                            </li>
                                            <li>
                                                Correo Electrónico
                                            </li>
                                            <li>
                                                Hosting Inlcuido
                                            </li>
                                            <li>
                                                Página Web
                                            </li>
                                            <li>
                                                Contactos Ilimitados
                                            </li>
                          
                                            <li>
                                                Soporte Técnico Lun-Dom 8:00 - 20:00
                                            </li>
                                            
                                          </ul>
                                        
                                    
                                          <?php endif; ?>
                                      </div>
                                    </div>
                        
                                </label>
                                
                              </div>
                                
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <div class="analysis-progrebar-ctn">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="">
                                    <button type="button" id="btn-cancel" onclick="window.location.replace('<?php echo e(url()->previous()); ?>')" class="btn btn-default">
                                            Cancelar &nbsp;<i class="fa fa-times"></i>
                                    </button>&nbsp;
                                    <button type="button" id="btn-save"
                                            class="btn btn-primary waves-effect waves-light">
                                            Enviar solicitud de cambio de plan&nbsp;<i class="fa fa-check"></i>
                                    </button>
                            </div>
                        </div>
                      </form>
                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
<script src="<?php echo e(asset('js/jquery.validate.js')); ?>"></script>
<script>
$("#btn-save").click(function(){
   
    if(!$("#form-plan").valid())
    {
        Lobibox.notify('error', {
            title: 'Notificación',
            position: 'top right',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "Favor de elegir un plan a cambiar"
        });

        return ;
    }

})
</script>
<?php if(Session::has('success')): ?>
    <script>
      Lobibox.alert( 'success', //AVAILABLE TYPES: "error", "info", "success", "warning"
        {
        msg: "Brokers Connector se comunicara con usted para autorizar el cambio de plan de 12 a 24 horas."
        });


    </script>
<?php endif; ?>
    
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/website/plans.blade.php ENDPATH**/ ?>