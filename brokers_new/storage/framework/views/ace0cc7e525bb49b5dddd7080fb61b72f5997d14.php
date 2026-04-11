<div class="flip-cards-row">

    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-home fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="<?php echo e(url('properties/index')); ?>">Propiedades</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-desktop fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="<?php echo e(url('home/website')); ?>">Sitio Web</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-globe fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="<?php echo e(route('show.all.stock')); ?>">Bolsa inmobiliaria</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-address-book fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="<?php echo e(route('contact.home')); ?>">Contactos</a></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-user fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="<?php echo e(route('users.index')); ?>">Usuarios</a></p>
                </div>
            </div>
        </div>
    </div>

    <?php if(auth()->check() && auth()->user()->hasRole('Admin')): ?>
    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-cogs fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="<?php echo e(route('setting.web')); ?>">Configuración</a></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>
<?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/includes/flip-cards.blade.php ENDPATH**/ ?>