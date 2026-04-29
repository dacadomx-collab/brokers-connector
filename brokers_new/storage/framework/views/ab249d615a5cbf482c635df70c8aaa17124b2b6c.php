<!-- Start Header menu area -->
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="logo-pro">
            <div class="sidebar-header">
                
                <a href="<?php echo e(url('home')); ?>"><img class="main-logo" src="<?php echo e(asset('img/logo/logo-recortado.png')); ?>" alt="Brokers Connector" style="width:200px;" /></a>
                <strong><a href="<?php echo e(url('home')); ?>"><img src="<?php echo e(asset('img/logo/mini-logo.png')); ?>" alt="" /></a></strong>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar ">
                <nav class="sidebar-nav left-sidebar-menu-pro ">
                    <ul class="metismenu" id="menu1">
                            <li>
                                <a class="text-center" href="<?php echo e(url('home')); ?>" style="border-bottom: 1px solid #ddd;border-top: 1px solid #ddd;">
                                    
                                        <span class="mini-click-non ">Panel de control</span>
                                    </a>
                                
                            </li>
                            
                        <li  <?php echo e(request()->is('properties*') ? 'class=active': ''); ?>>
                            <a class="has-arrow" href="#">
                                    <i class="fa fa-home fa-icon"></i>&nbsp;
								   <span class="mini-click-non">Propiedades</span>
								</a>
                            <ul class="submenu-angle" aria-expanded="false">

                                <li><a title="Subir una nueva propieada" href="<?php echo e(url('properties/create')); ?>"><span class="mini-sub-pro">Agregar propiedad</span></a></li>
                                
                                
                                
                                
                            </ul>
                        </li>
                        
                        <li <?php echo e(request()->is('home/contact*') ? 'class=active': ''); ?>>
                            <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-address-book fa-icon"></i>&nbsp;&nbsp;<span class="mini-click-non">Directorio</span></a>
                            <ul class="submenu-angle" aria-expanded="false">
                            <li ><a  title="Todos los contactos" href="<?php echo e(route('contact.home')); ?>"><span class="mini-sub-pro">Ver directorio</span></a></li>
                            <li><a title="Añadir contacto" href="<?php echo e(route('create.contact')); ?>"><span class="mini-sub-pro">Agregar contacto</span></a></li>
                              
                                
                            </ul>
                        </li>
                        
                        <li <?php echo e(request()->is('home/users*') ? 'class=active': ''); ?>>
                            <a class="has-arrow" href="#" aria-expanded="false"> <i class="fa fa-user fa-icon"></i>&nbsp;&nbsp;&nbsp;<span class="mini-click-non">Usuarios</span></a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li <?php echo e(request()->is('home/users') ? 'class=active': ''); ?>><a title="Ver todos los usuarios como agentes, entre otros" href="<?php echo e(route('users.index')); ?>"><span class="mini-sub-pro">Ver usuarios</span></a></li>
                                <li><a title="Agregar un nuevo usuario" href="<?php echo e(route('users')); ?>"><span class="mini-sub-pro">Agregar usuario</span></a></li>
                            </ul>
                        </li>
                        <li>
                            <a class="" href="<?php echo e(route('show.all.stock')); ?>">
                                <i class="fa fa-globe fa-icon"></i>&nbsp;
                                    <span class="mini-click-non ">Bolsa inmobiliaria</span>
                                </a>
                            
                        </li>
                       
                         <?php if(auth()->check() && auth()->user()->hasRole('Admin')): ?>
                        
                        <li <?php echo e(request()->is('home/v2*') ? 'class=active': ''); ?>>
                            <a href="<?php echo e(route('v2.subscription.bridge')); ?>"
                               title="Gestiona tu suscripción en el nuevo sistema">
                                <i class="fa fa-star fa-icon" aria-hidden="true"></i>&nbsp;
                                <span class="mini-click-non">
                                    ✨ Suscripción y Facturación
                                    <span class="label label-primary pull-right">Nuevo</span>
                                </span>
                            </a>
                        </li>

                        <li <?php echo e(request()->is('home/settings*') ? 'class=active': ''); ?>>
                                <a class="has-arrow" href="#" aria-expanded="false"> <i class="fa fa-cogs fa-icon"></i>&nbsp;<span class="mini-click-non">Configuración</span></a>
                                <ul class="submenu-angle" aria-expanded="false">
                                 <li><a title="Mi cuenta" href="<?php echo e(route('account')); ?>"><span class="mini-sub-pro">Mi Cuenta</span></a></li>
                                    
                                    <li><a title="Planes" href="<?php echo e(route('view.plans')); ?>"><span class="mini-sub-pro">Planes</span></a></li>
                                </ul>
                            </li>
                        <li <?php echo e(request()->is('/home/website') ? 'class=active': ''); ?>>
                            <a class="" href="<?php echo e(url('home/website')); ?>" aria-expanded="false"> <i class="fa fa-desktop fa-icon"></i>&nbsp;<span class="mini-click-non"> Sitio web</span></a>
                        </li>
                            <?php endif; ?>
                        
                    </ul>
                </nav>
            </div>
        </nav>
    </div>
    <!-- End Header menu area --><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/layouts/sidemenu.blade.php ENDPATH**/ ?>