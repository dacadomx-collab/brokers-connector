<!DOCTYPE html>
<html lang="es">
<head> <!-- <html class="no-js" lang="es">-->

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-154131747-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'UA-154131747-1');
    </script>

    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e(config('app.name', 'Brokersconnector')); ?></title>
    <meta name="description" content="Obtén rentas y ventas mas eficientes con resultados en menor tiempo, utlizando las herramientas necesarias para la administración y control de tus propiedades, colaborando con las asociaciones mas importantes">
    <meta name="keywords" content="software inmobiliario, sistema inmobiliario, crm inmobiliario, software de gestion inmobiliaria, software para inmobiliarias, interinmobiliario, gestion inmobiliaria, brokersconnector, brokersconnect, brokers, connector brokers"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index"/>

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="<?php echo e(asset('img/logo/img-logo-brokers2.png')); ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,700,900" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/bootstrap.min.css')); ?>">

    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/font-awesome.min.css')); ?>">

    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/owl.carousel.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/owl.theme.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/owl.transitions.css')); ?>">

    <!-- Animate CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/animate.css')); ?>">

    <!-- Normalize CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/normalize.css')); ?>">

    <!-- Meanmenu CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/meanmenu.min.css')); ?>">

    <!-- Main CSS (ARF-Grid centralizado) -->
    <link rel="stylesheet" href="<?php echo e(asset('css/main.css')); ?>">

    <!-- Educate Icon CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/educate-custon-icon.css')); ?>">

    <!-- Morris.js CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/morrisjs/morris.css')); ?>">

    <!-- mCustomScrollbar CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/scrollbar/jquery.mCustomScrollbar.min.css')); ?>">

    <!-- MetisMenu CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/metisMenu/metisMenu.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/metisMenu/metisMenu-vertical.css')); ?>">

    <!-- Style CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/style.css')); ?>">

    <!-- Responsive CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('admin/css/responsive.css')); ?>">

    <!-- Stack: estilos por vista -->
    <?php echo $__env->yieldPushContent('styles'); ?>

    <!-- Modernizr (debe ir en <head>) -->
    <script src="<?php echo e(asset('admin/js/vendor/modernizr-2.8.3.min.js')); ?>"></script>
</head>

<body>
    <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser.
        Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    <?php echo $__env->make('layouts.sidemenu', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="all-content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="logo-pro">
                        <a href="<?php echo e(url('/')); ?>"><img class="main-logo" src="<?php echo e(asset('img/logo/logo-recortado.png')); ?>" alt="Brokers Connector"></a>
                    </div>
                </div>
            </div>
        </div>

        <?php echo $__env->make('layouts.topbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <?php echo $__env->yieldContent('content'); ?>
    </div>

    <!-- ============ SCRIPTS — jQuery primero, siempre ============ -->

    <!-- jQuery (DEBE ser el primero) -->
    <script src="<?php echo e(asset('admin/js/vendor/jquery-1.12.4.min.js')); ?>"></script>

    <!-- Bootstrap JS -->
    <script src="<?php echo e(asset('admin/js/bootstrap.min.js')); ?>"></script>

    <!-- Wow JS -->
    <script src="<?php echo e(asset('admin/js/wow.min.js')); ?>"></script>

    <!-- Price Slider JS -->
    <script src="<?php echo e(asset('admin/js/jquery-price-slider.js')); ?>"></script>

    <!-- Meanmenu JS -->
    <script src="<?php echo e(asset('admin/js/jquery.meanmenu.js')); ?>"></script>

    <!-- Owl Carousel JS -->
    <script src="<?php echo e(asset('admin/js/owl.carousel.min.js')); ?>"></script>

    <!-- Sticky JS -->
    <script src="<?php echo e(asset('admin/js/jquery.sticky.js')); ?>"></script>

    <!-- ScrollUp JS -->
    <script src="<?php echo e(asset('admin/js/jquery.scrollUp.min.js')); ?>"></script>

    <!-- Counterup JS -->
    <script src="<?php echo e(asset('admin/js/counterup/jquery.counterup.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/counterup/waypoints.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/counterup/counterup-active.js')); ?>"></script>

    <!-- mCustomScrollbar JS -->
    <script src="<?php echo e(asset('admin/js/scrollbar/jquery.mCustomScrollbar.concat.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/scrollbar/mCustomScrollbar-active.js')); ?>"></script>

    <!-- MetisMenu JS -->
    <script src="<?php echo e(asset('admin/js/metisMenu/metisMenu.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/metisMenu/metisMenu-active.js')); ?>"></script>

    

    

    

    <!-- Plugins JS -->
    <script src="<?php echo e(asset('admin/js/plugins.js')); ?>"></script>

    <!-- Main JS -->
    <script src="<?php echo e(asset('admin/js/main.js')); ?>"></script>

    <!-- Stack: scripts por vista (después de jQuery) -->
    <?php echo $__env->yieldPushContent('scripts'); ?>

    
    <?php echo $__env->make('components.ai-chat', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</body>

</html>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/layouts/app.blade.php ENDPATH**/ ?>