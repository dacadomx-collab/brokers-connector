<?php $__env->startSection('title', ' Panel de control'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/modals.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/form/all-type-forms.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

    
    <?php if(Auth::user()->company == null): ?>
    <div class="analytics-sparkle-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 alert alert-danger" style="font-size:1.25rem;">
                    Para completar su registro, ingresa los datos de tu empresa.
                    <a href="<?php echo e(route('account')); ?>">Ir a tu cuenta</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="library-book-area">
        <div class="container-fluid">
            <div class="row">

                
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <?php echo $__env->make('includes.flip-cards', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>

                
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="dashboard-body">

                        <div class="dashboard-map">
                            <?php echo $__env->make('includes.map-properties', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </div>

                        <div class="dashboard-sidebar">
                            <?php echo $__env->make('includes.general-information', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    
    <?php echo $__env->make('includes.form-new-company', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('includes.modal-no-pay', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
    <script src="<?php echo e(asset('js/jquery.validate.js')); ?>"></script>
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

        <?php if(Auth::user()->company == null && Auth::user()->hasRole('Admin')): ?>
            $("#company_information").modal('show');
        <?php endif; ?>

        <?php if(Auth::user()->company != null): ?>
            <?php if(Auth::user()->company->active == 0): ?>
                $("#company_activate").modal('show');
            <?php endif; ?>
        <?php endif; ?>

        <?php if(Session::has('success')): ?>
            Lobibox.notify('success', {
                title: 'Información actualizada',
                position: 'top right',
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: "<?php echo e(session('success')); ?>"
            });
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                Lobibox.notify('error', {
                    title: 'Error',
                    position: 'top right',
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: "<?php echo e($error); ?>"
                });
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/home.blade.php ENDPATH**/ ?>