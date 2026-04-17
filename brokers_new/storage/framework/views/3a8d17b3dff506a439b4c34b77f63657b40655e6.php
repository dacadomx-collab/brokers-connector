<?php $__env->startSection('title','Lista de contactos'); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">
<style>
    .container {
        position: relative;

            {
                {
                --height: 14rem;
                --
            }
        }

            {
                {
                -- border: 1px solid;
                --
            }
        }
    }

    .jumbob {
        position: absolute;
        top: 50%;
        left: 50%;
        background: transparent;
        transform: translate(-50%, -50%);
        font-size: 18px !important;
        font-weight: inherit !important;
    }

    a i:hover {
        color: #000;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('/')); ?>">Inicio</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Lista de contactos</span></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="contacts-area mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <div class="sparkline8-list">
                   
                        <div class="static-table-list table-responsive">
                                <a class="btn btn-default pull-right" href="<?php echo e(route('create.contact')); ?>" >Agregar contacto</a>
                            <table class="table table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Tipo</th>
                                        <th>Estatus</th>
                                        <th>Origen</th>
                                        <th>Creado</th>
                                        <th></th>
                                        <th></th>

                                    </tr>
                                </thead>
                                <tbody>

                                    <?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr id="tr-<?php echo e($contact->id); ?>">
                                        <td><a data-toggle="tooltip" data-placement="top" title="Ver perfil"
                                                href="<?php echo e(route('contact.show', ['id' => $contact->id])); ?>"><?php echo e($contact->name); ?>

                                                <?php echo e($contact->surname); ?></a></td>
                                        <td><?php echo e($contact->email); ?></td>

                                        <?php if($contact->type): ?>
                                        <td><?php echo e(config('app.contact_types')[$contact->type]); ?></td>
                                        <?php else: ?>
                                        <td>No especificado</td>
                                        <?php endif; ?>

                                       <?php if($contact->status): ?>
                                       <td><?php echo e(config('app.contact_statuses')[$contact->status]); ?></td>
                                       <?php else: ?>
                                       <td>No especificado</td>
                                       <?php endif; ?>

                                       <?php if($contact->origin): ?>
                                       <td><?php echo e(config('app.contact_origins')[$contact->origin]); ?></td>
                                       <?php else: ?>
                                       <td>No especificado</td>
                                       <?php endif; ?>
                                       
                                        <td><?php echo e($contact->m_created); ?></td>
                                        <td style="display:inline-flex"><a data-toggle="tooltip" data-placement="top"
                                                title="Editar contacto"
                                                href="<?php echo e(route('contact.edit', ['id'=>$contact->id])); ?>" class="">
                                                <i style="font-size: x-large" class="fa fa-fw fa-edit"></i></a>
                                            <button data-toggle="tooltip" class="btn-delete" data-placement="top"
                                                title="Eliminar contacto" id="<?php echo e($contact->id); ?>"
                                                data-id="<?php echo e($contact->id); ?>"
                                                style="background: transparent;border: unset;font-size: x-large;color: #e12503;transform: translateY(-3.5px);"><i
                                                    class="fa fa-fw fa-times"></i></button></td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>


                        </div>
                    </div>
                        <?php if($contacts->count()<=0): ?> 
                        <div class=" h-100 d-flex justify-content-center align-items-center" style="
                        padding-top: 150px;
                    ">
                            <div class="container theme-showcase" role="main">

                                <div class="jumbob">
                                    <h2 style="">Aún no tienes contactos <i class="fa fa-sad-tear"></i></h2>
                                    <p>Intenta agregar algunos</p>
                                    <a href="<?php echo e(route('create.contact')); ?>" class="btn btn-default">AGREGAR</a>
                                </div>

                            </div>

                            <?php endif; ?>
                            <div align="center">
                                <?php echo e($contacts->links()); ?>

                            </div>
                   
                </div>

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
        msg: "<?php echo e(session('success')); ?>",
        

    });

</script>
<?php endif; ?>

<?php if($errors->any()): ?>

<script>
    <?php $__currentLoopData = $errors -> all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
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
    $(".btn-delete").click(function () {
        var id = $(this).data("id");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "Seguro desea eliminar a este contacto?",
            buttons: {
                yes: {
                    class: 'btn btn-primary',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                }
            },
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                    $.ajax(
                        {
                            type: "post",
                            url: "/home/contact/delete",
                            data: { user_id: id },
                            success: function (result) {
                                $("#tr-" + id).html("");
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
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/contact/list.blade.php ENDPATH**/ ?>