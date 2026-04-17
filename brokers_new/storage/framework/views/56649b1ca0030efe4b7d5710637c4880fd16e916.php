<?php $__env->startSection('title','Lista de usuarios'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">


<link rel="stylesheet" href="<?php echo e(asset('admin/css/form/all-type-forms.css')); ?>">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('breadcome'); ?>
    <li><a href="<?php echo e(url('home')); ?>">Inicio</a> <span class="bread-slash">/</span></li>
    <li><span class="bread-blod">Lista de usuarios</span></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="contacts-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
            
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12" id="row-<?php echo e($user->id); ?>" style="padding-top: 20px;">
                    <div class="hpanel hblue contact-panel contact-panel-cs" style="min-height: 320px;">
                        <div class="panel-body custom-panel-jw">
                            
                               
                                
                                    

                                    
                                    <?php if($user->id==auth()->user()->id || auth()->user()->hasRole("Admin")): ?>

                                        <div style="position: absolute;right: -10px;top: -13px;">
                                            
                                            <a class="dropdown-toggle"  data-toggle="dropdown" href="#">
                                                <i class="fa fa-ellipsis-v fa-2x" style="padding: 10px;" aria-hidden="true"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right w-100" style="padding: 9px 0;" role="menu">
                                                <li><a href="<?php echo e(route('users.showEdit',$user->id)); ?>" style="margin-top:0;">
                                                    <i class="fa fa-pencil fa-lg" style="color:#324a5e;" aria-hidden="true"></i>&nbsp; Editar</a></li>
                                                    <?php if($user->hasRole("Admin") || auth()->user()->id==$user->id): ?>
                                                    
                                                    <?php else: ?>
                                                <li class="divider"></li>
                                                <li><a href="#" class="btn-delete" data-id="<?php echo e($user->id); ?>" style="margin-top:0;">
                                                    <i class="fa fa-times fa-lg" style="color:#F44336" aria-hidden="true"></i>&nbsp; Eliminar</a></li>
                                                    <?php endif; ?>
                                            </ul>
                                        </div>
                         
                                    <?php endif; ?>
                                    
                                    
                               
                               
                            
                            <div class="text-center">
                                    <img alt="logo" class="img-circle m-b" src="<?php echo e($user->foto_avatar); ?>" style="height:137px; margin-left: auto;
                                    margin-right: auto; display:block; width: 134px; object-fit: cover;">
                            </div>
                            <br>

                            <div class="text-center" style="color:#777;">
                                <h4><a style="font-size:20px;color:#3077b7;" href="#"><?php echo e(ucwords($user->f_name)); ?></a></h4>
                                <p class="all-pro-ad"><?php echo e($user->roles->pluck("display_name")->implode(", ")); ?></p>

                                <div><span style="font-size:15px;" ><?php echo e($user->phone); ?></span></div>
                                <div><strong><?php echo e($user->email); ?></strong></div>
                            </div>
                          
                                
                            
                        </div>
                        
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            </div>
        </div>
    </div>
</div>





<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
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

<?php if($errors->any()): ?>

<script>
     <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        Lobibox.notify('error', {
            title: 'Error',
            verticalOffset: 5,
            position: 'top right',
            height: 'auto',      
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "<?php echo e($error); ?>"
            
        });
     <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</script>

<?php endif; ?>

<script>
    $(".btn-delete").click(function(){
       var id= $(this).data("id");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "Seguro desea eliminar a este usuario?",
            buttons: {
                yes: {
                    class: 'btn btn-default',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                }
            },
            callback: function ($nop, type, ev) 
            {
               if(type=="yes")
               {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                    $.ajax(
                    {   type:"post",
                        url: "/home/users/delete", 
                        data: {user_id: id},
                        success: function(result){
                            $("#row-"+id).html("");
                            Lobibox.notify('success', {
                                title: 'Exito',
                                position: 'top right',
                                showClass: 'fadeInDown',
                                hideClass: 'fadeUpDown',
                                msg: result
                            });
                        }
                    });
               }
            }
        });   
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/users/list.blade.php ENDPATH**/ ?>