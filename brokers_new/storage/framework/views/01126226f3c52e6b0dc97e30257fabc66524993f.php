<?php $__env->startSection('content'); ?>
     <!-- banner part start-->
     <section class="banner_part" id="banner_part">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <div class="banner_text">
                        <div class="banner_text_iner">
                            <h1 >Aumenta tus ventas y consigue mas clientes</h1>
                            
                            <p></p>
                            <?php if(Auth::check()): ?>
                            <a role="button" href="<?php echo e(url('home')); ?>" style="font-size:initial;"
                                class="btn_2 banner_btn_1">Mi cuenta</a>
                            <?php else: ?>
                            <button data-izimodal-open="#modal-custom" style="font-size:initial;"
                                class="btn_2 banner_btn_1">Registrarse</button>
                            <a role="button" href="<?php echo e(route('login')); ?>" style="font-size:initial;" class="btn_2 banner_btn_2">Iniciar
                                sesión</a>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="banner_img d-none d-lg-block">
                        <img style="max-width: 109%;" src="<?php echo e(asset('img/homeimg.png')); ?>" alt="home">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- banner part start-->

    <!-- feature_part start-->
    <section id="benefits" class="feature_part padding_top">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-lg-6 ">
                    <div class="row align-items-center">

                        <div class="col-lg-6 col-sm-6">
                            <div class="single_feature">
                                <div class="single_feature_part">
                                    <img src="<?php echo e(asset('img/time.svg')); ?>" alt="">
                                    <h4>Ahorra tiempo</h4>
                                    <p>Vinculación y publicación de tus inmuebles en los portales mas conocidos</p>
                                </div>
                            </div>
                            <div class="single_feature">
                                <div class="single_feature_part single_feature_part_2">
                                    <img src="<?php echo e(asset('img/website.png')); ?>" alt="">
                                    <h4>Sitio web</h4>
                                    <p>Tu propio sitio web con dominio personalizado, elige el diseño que mas te guste
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-sm-6">
                            <div class="single_feature">
                                <div class="single_feature_part">
                                    <img src="<?php echo e(asset('img/multitask.svg')); ?>" alt="">
                                    <h4>Organización</h4>
                                    <p>Lleva toda tu cartera de clientes e inmuebles en un único lugar</p>
                                </div>
                            </div>
                            <div class="single_feature">
                                <div class="single_feature_part single_feature_part_2">
                                    
                                    <h4>Colaboración</h4>
                                    <p>No pierdas ninguna oportunidad de negocio, tu eliges con quien colaborar</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="feature_part_text">
                        <h2>Beneficios</h2>
                        <p>Obtén rentas y ventas mas eficientes con resultados en menor tiempo, utlizando las
                            herramientas necesarias para la administración y control de tus propiedades, colaborando con
                            las asociaciones mas importantes.</p>
                        
                    </div>
                </div>
            </div>
        </div>

        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape-1.png')); ?>" alt="" class="feature_icon_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape-14.png')); ?>" alt="" class="feature_icon_2">
        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape.png')); ?>" alt="" class="feature_icon_3">
        
    </section>
    <!-- upcoming_event part start-->

    <!-- about_us part start-->
    <section class="about_us section_padding">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-md-6 col-lg-5">
                    <div class="about_us_text">
                        <h2>Comparte facilmente tus propiedades</h2>
                        <p></p>
                       <?php if(auth()->guard()->guest()): ?>
                       
                       <button data-izimodal-open="#modal-custom" class="btn_1">Comenzar</button>
                       <button data-izimodal-open="#modal-custom" class="btn_2">Registrate</button>
                       <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6">
                    <div class="learning_img">
                        <img src="<?php echo e(asset('welcome/img/about_img.png')); ?>" alt="">
                    </div>
                </div>
            </div>
        </div>
        <img src="<?php echo e(asset('welcome/img/left_sharp.png')); ?>" alt="" class="left_shape_1">
        <img src="<?php echo e(asset('welcome/img/about_shape.png')); ?>" alt="" class="about_shape_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape-16.png')); ?>" alt="" class="feature_icon_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape-1.png')); ?>" alt="" class="feature_icon_4">
    </section>
    <!-- about_us part end-->

    <!-- use_sasu part end-->
    
    <!-- use_sasu part end-->

    <!-- about_us part start-->
    <section style="padding-top: 0;" class="about_us right_time section_padding">
        <div class="container">
            <div class="row align-items-center justify-content-between">
                <div class="col-md-6 col-lg-6">
                    <div class="learning_img">
                        <img src="<?php echo e(asset('welcome/img/about_img_1.png')); ?>" alt="">
                    </div>
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="about_us_text">
                        <h2>App Móvil Android/iOS</h2>
                        <p>Toda la información y gestión siempre disponible en tu teléfono móvil o tablet.
                            Aplicación disponible para Android y iOS (iPhone y iPad). </p>

                            <?php if(auth()->guard()->guest()): ?>    
                            <button data-izimodal-open="#modal-custom" class="btn_1">Comenzar</button>
                            <button data-izimodal-open="#modal-custom" class="btn_2">Registrate</button>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <img src="<?php echo e(asset('welcome/img/about_shape.png')); ?>" alt="" class="about_shape_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape-1.png')); ?>" alt="" class="feature_icon_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/shape.png')); ?>" alt="" class="feature_icon_4">
    </section>
    <!-- about_us part end-->

    <!-- pricing part start-->
    <section id="price" class="pricing_part mb_130 home_page_pricing">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="section_tittle text-center">
                        <h2>Precios</h2>
                        <p></p>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-3 col-sm-6">
                    <div class="text-center ">Plan socio <strong>ASPI</strong> / <strong>AMPI</strong></div>
                    <div class="single_pricing_part">

                        
                        <p>Single</p>
                        <h3>$600<span style="font-size: 16px;">MXN</span> <span>/ mes</span></h3>
                        <ul>
                            <li>Propiedades ilimitadas</li>
                            <li>Contactos ilimitados</li>
                            <li>Página web </li>
                            <li>Hosting inlcuido</li>
                            <li>Correo electrónico</li>
                            <li>Soporte técnico Lun-Dom 8:00 - 20:00 </li>
                            <li>&nbsp;</li>
                        </ul>
                        <a href="#" class="btn_1">Elige tu plan</a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="text-center ">Plan socio <strong>ASPI</strong> / <strong>AMPI</strong></div>

                    <div class="single_pricing_part">
                        
                        <p>Corporation</p>
                        <h3>$780<span style="font-size: 16px;">MXN</span> <span>/ mes</span></h3>
                        <ul>
                            <li>$50 por usuario extra <i style="margin-left:5px;" class="fa fa-user-plus"></i></li>
                            <li>Propiedades ilimitadas</li>
                            <li>Contactos ilimitados</li>
                            <li>Página web </li>
                            <li>Hosting inlcuido</li>
                            <li>Correo electrónico</li>
                            <li>Soporte técnico Lun-Dom 8:00 - 20:00 </li>
                        </ul>
                        <a href="#" class="btn_1">Elige tu plan</a>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="text-center ">Plan <strong>General</strong></div>

                    <div class="single_pricing_part">
                        

                        
                        <p>General</p>
                        <h3>$1200<span style="font-size: 16px;">MXN</span> <span>/ mes</span></h3>
                        <ul>
                            <li>$100 por usuario extra <i style="margin-left:5px;" class="fa fa-user-plus"></i></li>
                            <li>Propiedades ilimitadas</li>
                            <li>Contactos ilimitados</li>
                            <li>Página web </li>
                            <li>Hosting inlcuido</li>
                            <li>Correo electrónico</li>
                            <li>Soporte técnico Lun-Dom 8:00 - 20:00 </li>

                        </ul>
                        <a href="#" class="btn_1">Elige tu plan</a>
                    </div>
                </div>
            </div>
            <div>
                <div class="row">
                    <div class="col-12" style="">
                        <p class="text-center" style="color:#0027ff;font-weight:500;font-size:18px">*Estos precios no
                            incluyen IVA</p>
                        <p class="text-center" style="color:#0a1a73;font-size:18px;">*Dominio no incluido, precios se
                            cotizan por separado en todos los planes</p>
                    </div>
                </div>

            </div>
        </div>
        <img src="<?php echo e(asset('welcome/img/left_sharp.png')); ?>" alt="" class="left_shape_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/Shape-1.png')); ?>" alt="" class="feature_icon_1">
        <img src="<?php echo e(asset('welcome/img/animate_icon/shape.png')); ?>" alt="" class="feature_icon_4">
    </section>
    <!-- pricing part end-->

    
    <!--::footer_part start::-->
   

    <div id="modal-custom" data-iziModal-group="grupo1">
        <button data-iziModal-close class="genric-btn danger arrow"
            style="float:right; font-size:large;display:block;">x</button>
        <br>
        <br>
        <br>
        <div class="card-body">
            <form method="POST" id="form-control" action="<?php echo e(route('register')); ?>">
                <?php echo csrf_field(); ?>

                <div class="form-group row">
                    <label for="full_name" class="col-md-4 col-form-label text-md-right"><?php echo e(__('Nombre(s)')); ?></label>

                    <div class="col-md-6">
                        <input id="full_name" type="text" class="form-control <?php if ($errors->has('full_name')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('full_name'); ?> is-invalid <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>"
                            name="full_name" required value="<?php echo e(old('full_name')); ?>" autocomplete="full_name" autofocus>

                        <span class="invalid-feedback" id="full_name-errors" role="alert" style="display:hidden;">

                        </span>
                        <?php if ($errors->has('full_name')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('full_name'); ?>
                        <span class="invalid-feedback" role="alert">
                            <strong><?php echo e($message); ?></strong>
                        </span>
                        <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="last_name" class="col-md-4 col-form-label text-md-right"><?php echo e(__('Apellidos(s)')); ?></label>

                    <div class="col-md-6">
                        <input id="last_name" type="text" class="form-control <?php if ($errors->has('last_name')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('last_name'); ?> is-invalid <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>"
                            name="last_name" required value="<?php echo e(old('last_name')); ?>" autocomplete="last_name" autofocus>

                        <span class="invalid-feedback" id="last_name-errors" role="alert" style="display:hidden;">

                        </span>
                        <?php if ($errors->has('last_name')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('last_name'); ?>
                        <span class="invalid-feedback" role="alert">
                            <strong><?php echo e($message); ?></strong>
                        </span>
                        <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="email"
                        class="col-md-4 col-form-label text-md-right"><?php echo e(__('Correo eléctronico')); ?></label>

                    <div class="col-md-6">
                        <input id="email" type="email" class="form-control <?php if ($errors->has('email')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('email'); ?> is-invalid <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>"
                            required name="email" value="<?php echo e(old('email')); ?>" autocomplete="email">

                        <span class="invalid-feedback" id="email-errors" role="alert" style="display:hidden;">

                        </span>

                        <?php if ($errors->has('email')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('email'); ?>
                        <span class="invalid-feedback" role="alert">
                            <strong><?php echo e($message); ?></strong>
                        </span>
                        <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="password"
                        class="col-md-4 col-form-label text-md-right"><?php echo e(__('Contraseña')); ?></label>

                    <div class="col-md-6">
                        <input id="password" type="password" minlength="8"
                            class="form-control <?php if ($errors->has('password')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('password'); ?> is-invalid <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>" name="password" required
                            autocomplete="new-password">

                        <span class="invalid-feedback" id="password-errors" role="alert" style="display:hidden;">

                        </span>

                        <?php if ($errors->has('password')) :
if (isset($message)) { $messageCache = $message; }
$message = $errors->first('password'); ?>
                        <span class="invalid-feedback" role="alert">
                            <strong><?php echo e($message); ?></strong>
                        </span>
                        <?php unset($message);
if (isset($messageCache)) { $message = $messageCache; }
endif; ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="password-confirmation"
                        class="col-md-4 col-form-label text-md-right"><?php echo e(__('Confirmar contraseña')); ?></label>

                    <div class="col-md-6">
                        <input id="password-confirm" minlength="8" type="password" class="form-control"
                            name="password_confirmation" autocomplete="new-password">

                        <span class="invalid-feedback" id="password-confirm-errors" role="alert"
                            style="display:hidden;">

                        </span>
                    </div>
                </div>
               
                    
               

                <div class="form-group row mb-0 text-center">
                    
                   <div class="col-lg-12">
                       <button type="button" id="btn-submit" class="btn btn-primary">
                           Registrarse
                       </button>

                   </div>
                   <br>

                    <span class="info text-center" style="    margin-left: auto;margin-right: auto;margin-top: 22px;
                    color: gray;
                    font-size: 10px;">
                        Al registrarse acepta las <a href="<?php echo e(url('privacy-politics')); ?>" target="_blank">Politicas de privacidad</a> y los <a href="<?php echo e(url('terms-conditions')); ?>" target="_blank">Terminos y condiciones</a>
                    </span>
                </div>
            </form>
        </div>
        <br>
    </div>
    <!--::footer_part end::-->

    <!-- jquery plugins here — jQuery primero -->
    <script src="<?php echo e(asset('welcome/js/jquery-1.12.1.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/popper.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/bootstrap.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/jquery.magnific-popup.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/swiper.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/masonry.pkgd.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/owl.carousel.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/jquery.nice-select.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/slick.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/jquery.counterup.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/waypoints.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/contact.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/jquery.ajaxchimp.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/jquery.form.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/jquery.validate.min.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/mail-script.js')); ?>"></script>
    <script src="<?php echo e(asset('welcome/js/form-validate.js')); ?>"></script>
    <!-- iziModal — después de jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.5.1/js/iziModal.js"
        integrity="sha256-jx5SpNrWp5tIlHK2uGtUsZ4QRJkEV9aQXXGN3kkPXIE=" crossorigin="anonymous"></script>
    <script src="<?php echo e(asset('welcome/js/custom.js')); ?>"></script>
    <script>

        $(document).ready(function(){
            $(this).scrollTop(0);
        });

        $("#modal-custom").iziModal({
            overlayClose: false,
            overlayColor: 'rgba(0, 0, 0, 0.6)'
        });

        <?php if($errors->any()): ?>
        $("#modal-custom").iziModal('open');
        <?php endif; ?>




        $('a.ancla').click(function (e) {
            e.preventDefault(); //evitar el eventos del enlace normal
            var strAncla = $(this).attr('href'); //id del ancla
            $('body,html').stop(true, true).animate({
                scrollTop: $(strAncla).offset().top
            }, 1500);

        });

    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('welcome.layouts.app', ["contract"=> false], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/welcome/index.blade.php ENDPATH**/ ?>