<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>BrokersConnector</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo e(asset('img/logo/img-logo-brokers2.png')); ?>">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/bootstrap.min.css')); ?>">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/animate.css')); ?>">
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/owl.carousel.min.css')); ?>">
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/all.css')); ?>">
    <!-- Flaticon CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/flaticon.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/themify-icons.css')); ?>">
    <!-- Magnific Popup CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/magnific-popup.css')); ?>">
    <!-- Slick CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/slick.css')); ?>">
    <!-- iziModal CSS (CDN externo — sin subcarpeta) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.5.1/css/iziModal.css"
        integrity="sha256-qaS6Cn77YhfgOLFHy4qadvrn/cEYG9bvbnQILtSY+0E=" crossorigin="anonymous">
    <!-- Style CSS -->
    <link rel="stylesheet" href="<?php echo e(asset('welcome/css/style.css')); ?>">
</head>

<body>
    <header class="main_menu home_menu" style="background-color:#4caac9;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12">
                    <nav class="navbar navbar-expand-lg">
                        <a class="navbar-brand" href="<?php echo e(url('/')); ?>">
                            <img src="https://www.brokersconnector.com/img/email/logo.png" alt="Brokers Connector">
                        </a>
                        <button class="navbar-toggler" type="button" data-toggle="collapse"
                            data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="Toggle navigation">
                            <span class="menu_icon"><i class="fas fa-bars"></i></span>
                        </button>

                        <div class="collapse navbar-collapse main-menu-item" id="navbarSupportedContent">
                            <ul class="navbar-nav">
                                <li class="nav-item">
                                    <a class="nav-link ancla" href="<?php echo e($contract ? '/' : '#banner_part'); ?>">Inicio</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link ancla" href="<?php echo e($contract ? '/#benefits' : '#benefits'); ?>">Beneficios</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link ancla" href="<?php echo e($contract ? '/#price' : '#price'); ?>">Precios</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link ancla" href="<?php echo e($contract ? '/#contact' : '#contact'); ?>">Contacto</a>
                                </li>
                                <?php if(auth()->guard()->guest()): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo e(route('login')); ?>">Acceder</a>
                                </li>
                                <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo e(url('home')); ?>">Mi cuenta</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>

                    </nav>
                </div>
            </div>
        </div>
    </header>

    <?php echo $__env->yieldContent('content'); ?>

    <footer class="footer_part" id="contact">
        <hr>
        <div class="container">
            <div class="row">
                <div class="col-sm-12 col-lg-12">
                    <div class="single_footer_part">
                        <h4>Información de contacto</h4>
                        <p>Email : sistemas@brokersconnector.com</p>
                        <p>Dirección : Ignacio Allende 270 entre Revolución y Serdan, col. Centro, CP: 23000</p>
                        <p>La Paz, Baja California Sur, México</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/welcome/layouts/app.blade.php ENDPATH**/ ?>