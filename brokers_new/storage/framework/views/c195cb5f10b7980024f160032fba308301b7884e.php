<!DOCTYPE html>
<html lang="en">
<head>
	<title>Iniciar Sesión</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="<?php echo e(asset('img/logo/img-logo-brokers2.png')); ?>"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/vendor/bootstrap/css/bootstrap.min.css')); ?>">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/fonts/font-awesome-4.7.0/css/font-awesome.min.css')); ?>">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/vendor/animate/animate.css')); ?>">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/vendor/css-hamburgers/hamburgers.min.css')); ?>">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/vendor/select2/select2.min.css')); ?>">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/css/util.css')); ?>">
	<link rel="stylesheet" type="text/css" href="<?php echo e(asset('login-broker/css/main.css')); ?>">
<!--===============================================================================================-->
</head>
<body>
	<div class="limiter">
		<div class="container-login100" style="background:#4CAACA;"> 
			<div class="wrap-login100">
                   
            <div class="login100-pic js-tilt" data-tilt style="margin-top: 17% !important;">
					<img class="main-logo" src="<?php echo e(asset('img/logo/logo-recortado.png')); ?>"  />
				</div>

				<form class="login100-form validate-form" method="POST" action="<?php echo e(route('login')); ?>">
                    <?php echo csrf_field(); ?>
                    <div class="col-md-12 row">
                        <?php if($errors->any()): ?>
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="alert alert-danger w-100"><?php echo e($error); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> 
                        <?php endif; ?>
                        <?php if(session('message-error-confirm')): ?>
                            <span class="alert alert-danger w-100">
                                <?php echo e(session('message-error-confirm')); ?>

                            </span>
                        <?php endif; ?>
                        <?php if(session('message-success-confirm')): ?>
                            <span class="alert alert-danger w-100">
                                <?php echo e(session('message-success-confirm')); ?>

                            </span>
                        <?php endif; ?>
                    </div>
					<span class="login100-form-title">
						Ingresar
					</span>

					<div class="wrap-input100 validate-input" data-validate = "Valid email is required: ex@abc.xyz">
						<input class="input100" type="email" name="email" value="<?php echo e(old('email')); ?>" email placeholder="Correo electroníco" required>
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-envelope" aria-hidden="true"></i>
						</span>
					</div>

					<div class="wrap-input100 validate-input" data-validate = "Password is required">
						<input class="input100" type="password" name="password" placeholder="Contraseña" required>
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>
					
					<div class="container-login100-form-btn">
						<button class="login100-form-btn" style="background-color:#3077b7 !important;">
							Ingresar
						</button>
					</div>

					<div class="text-center p-t-12">
                        <a class="txt2" href="<?php echo e(route('password.request')); ?>">
                         ¿Olvidó su contraseña?
                        </a>
                        <br>
						<a class="txt2" href="<?php echo e(url('/')); ?>">
							Regresar al inicio
						</a>
					</div>

					<div class="text-center p-t-136">
						
					</div>
				</form>
			</div>
		</div>
	</div>
	
	

	
<!--===============================================================================================-->	
	<script src="<?php echo e(asset('login-broker/vendor/jquery/jquery-3.2.1.min.js')); ?>"></script>
<!--===============================================================================================-->
	<script src="<?php echo e(asset('login-broker/vendor/bootstrap/js/popper.js')); ?>"></script>
	<script src="<?php echo e(asset('login-broker/vendor/bootstrap/js/bootstrap.min.js')); ?>"></script>
<!--===============================================================================================-->
	<script src="<?php echo e(asset('login-broker/vendor/select2/select2.min.js')); ?>"></script>
<!--===============================================================================================-->
	<script src="<?php echo e(asset('login-broker/vendor/tilt/tilt.jquery.min.js')); ?>"></script>
	<script>
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
<!--===============================================================================================-->
	<script src="<?php echo e(asset('login-broker/js/main.js')); ?>"></script>

</body>


<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/auth/login.blade.php ENDPATH**/ ?>