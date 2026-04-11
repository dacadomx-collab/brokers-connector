<div class="single-review-st-item res-mg-t-30 table-mg-t-pro-n">
    <div class="single-review-st-hd">
        <h2 class="text-center">Información</h2>
    </div>

    <?php if(auth()->user()->company): ?>
    <div class="info-widget">
        <i class="fa fa-globe"></i>
        <p class="all-pro-ad">Mi sitio web</p>
        <?php if(!$company->dominio): ?>
            <h5>Creando sitio web (esto puede tardar de 8 a 24 horas)</h5>
        <?php else: ?>
            <h5><a target="_blank" href="http://<?php echo e($company->dominio); ?>"><?php echo e($company->dominio); ?></a></h5>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="info-widget">
        <i class="fa fa-briefcase"></i>
        <p class="all-pro-ad">Pagos</p>
        <h5><a href="<?php echo e(route('invoices')); ?>">Estado de cuenta</a></h5>
    </div>

    <div class="info-widget">
        <i class="fa fa-home"></i>
        <p class="all-pro-ad">Propiedades publicadas</p>
        <h5><a href="<?php echo e(url('properties/index')); ?>"><?php echo e($number_properties ?? 0); ?></a></h5>
    </div>

</div>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/includes/general-information.blade.php ENDPATH**/ ?>