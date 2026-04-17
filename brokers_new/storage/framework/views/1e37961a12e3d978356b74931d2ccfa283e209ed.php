<div class="row">
    <div class="col-md-4">
        <div class="row">
            <div class="col-md-12">
                <img id="preview-image" style="width: 18em;margin: 0 auto;height: 18em;display: block;border-radius: 50%;object-fit: cover;" src="<?php echo e(!$edit ? "http://www.rafacademy.com/wp-content/uploads/2017/03/user-default.png" : $data->FotoAvatar); ?>"
                    alt="">
            </div>
            <div class="col-md-12 text-center" style="margin-top:20px">
                <label style="background: #303030;color: #fff;border: 1px solid #333;" class="btn"
                    for="input-image">Subir imagen</label>
                <input type="file" name="file" id="input-image" accept="image/*" style="opacity:0; position:absolute;">
            </div>
        </div>
    </div>
    <div class="col-md-8">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('full_name')): ?> has-error <?php endif; ?>">
                        <label for="full_name">Nombre(s) <span style="color:red;">*</span></label>
                        <input required type="text" class="form-control" maxlength="255" name="full_name"  autocomplete="off"
                            value="<?php echo e(old('full_name',$data->full_name)); ?>" placeholder="Ingresar Nombre(s)">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('last_name')): ?> has-error <?php endif; ?>">
                        <label for="surname">Apellido(s) <span style="color:red;">*</span></label>
                        <input required type="text" class="form-control" maxlength="255" name="last_name"  autocomplete="off"
                            value="<?php echo e(old('last_name', $data->last_name)); ?>" placeholder="Apellido(s)">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('title')): ?> has-error <?php endif; ?>">
                        <label for="surname">Título</label>
                        <input type="text" class="form-control" maxlength="255" name="title" autocomplete="off"
                            value="<?php echo e(old('title', $data->title)); ?>" placeholder="Ingresar el título">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('phone')): ?> has-error <?php endif; ?>">
                        <label for="">Teléfono</label>
                        <input type="text" class="form-control phone-input" maxlength="13" name="phone"  autocomplete="off"
                            value="<?php echo e(old('phone', $data->phone)); ?>" placeholder="Ingresar un teléfono">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('email')): ?> has-error <?php endif; ?>">
                        <label for=""> Correo electrónico <span style="color:red;">*</span></label>
                        <input required type="email" class="form-control" maxlength="255" name="email" autocomplete="off"
                            value="<?php echo e(old('email', $data->email)); ?>" placeholder="Ingresa un correo electrónico valido">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('rol')): ?> has-error <?php endif; ?>">
                        <label for="rol">Tipo:</label>
                        <select name="user_a" class="form-control" id="rol">
                        <?php if($user->hasrole("Admin") && $edit): ?>
                            
                            <option value="<?php echo e($user->Roles()->first()->id); ?>"><?php echo e($user->Roles()->first()->display_name); ?></option>
                        <?php else: ?>
                            <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value->name); ?>"><?php echo e($value->display_name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group  <?php if($errors->has('password')): ?> has-error <?php endif; ?>">
                        <label for="">Contraseña <?php echo !$edit ? '<span style="color:red;">*</span>' : ""; ?> </label>
                        <input id="pass" <?php echo e(!$edit ? "required" : ""); ?> type="password" class="form-control" minlength="8" autocomplete="off"
                            name="password" placeholder="Ingresa una contraseña">
                        <?php if($edit): ?>
                        <span class="info">Ingresar en caso de nueva contraseña</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group <?php if($errors->has('password_confirmation')): ?> has-error <?php endif; ?>">
                        <label for="">Confirmar contraseña <?php echo !$edit ? '<span style="color:red;">*</span>':
                            ""; ?></label>
                        <input <?php echo e(!$edit ? "required" : ""); ?> type="password" class="form-control" minlength="8" autocomplete="off"
                            name="password_confirmation" placeholder="Ingresar una confirmación de contraseña">
                    </div>
                </div>
            </div>
    </div>
</div>


<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="margin-top:35px;">
    <a href="<?php echo e(route('users.index')); ?>" role="button" id="btn-cancel" class="btn btn-default">
        Cancelar &nbsp;<i class="fa fa-times"></i><a>
    </button>&nbsp;
    <button type="button" id="btn-save"  class="btn btn-primary waves-effect waves-light">
        Guardar &nbsp;<i class="fa fa-check"></i>
    </button>
</div>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/users/utils/form.blade.php ENDPATH**/ ?>