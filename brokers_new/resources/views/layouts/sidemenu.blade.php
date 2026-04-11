<!-- Start Header menu area -->
    <div class="left-sidebar-pro">
        <nav id="sidebar" class="logo-pro">
            <div class="sidebar-header">
                {{-- este es mi boton --}}
                <a href="{{ url('home') }}"><img class="main-logo" src="{{ asset('img/logo/logo-recortado.png') }}" alt="Brokers Connector" style="width:200px;" /></a>
                <strong><a href="{{ url('home') }}"><img src="{{ asset('img/logo/mini-logo.png') }}" alt="" /></a></strong>
            </div>
            <div class="left-custom-menu-adp-wrap comment-scrollbar ">
                <nav class="sidebar-nav left-sidebar-menu-pro ">
                    <ul class="metismenu" id="menu1">
                            <li>
                                <a class="text-center" href="{{ url('home') }}" style="border-bottom: 1px solid #ddd;border-top: 1px solid #ddd;">
                                    
                                        <span class="mini-click-non ">Panel de control</span>
                                    </a>
                                
                            </li>
                            
                        <li  {{ request()->is('properties*') ? 'class=active': '' }}>
                            <a class="has-arrow" href="#">
                                    <i class="fa fa-home fa-icon"></i>&nbsp;
								   <span class="mini-click-non">Propiedades</span>
								</a>
                            <ul class="submenu-angle" aria-expanded="false">

                                <li><a title="Subir una nueva propieada" href="{{ url('properties/create') }}"><span class="mini-sub-pro">Agregar propiedad</span></a></li>
                                {{--  <li><a title="Dashboard v.2" href="index-1.html"><span class="mini-sub-pro">propiedades de mis aliados</span></a></li>  --}}
                                {{-- <li><a title="Dashboard v.3" href="/properties/import"><span class="mini-sub-pro">Importar propiedades</span></a></li> --}}
                                {{--  <li><a title="Analytics" href="analytics.html"><span class="mini-sub-pro">Analytics</span></a></li>  --}}
                                {{--  <li><a title="Widgets" href="widgets.html"><span class="mini-sub-pro">Widgets</span></a></li>  --}}
                            </ul>
                        </li>
                        {{--  <li>
                            <a title="Landing Page" href="events.html" aria-expanded="false"><span class="educate-icon educate-event icon-wrap sub-icon-mg" aria-hidden="true"></span> <span class="mini-click-non">Event</span></a>
                        </li>  --}}
                        <li {{ request()->is('home/contact*') ? 'class=active': '' }}>
                            <a class="has-arrow" href="#" aria-expanded="false"><i class="fa fa-address-book fa-icon"></i>&nbsp;&nbsp;<span class="mini-click-non">Directorio</span></a>
                            <ul class="submenu-angle" aria-expanded="false">
                            <li ><a  title="Todos los contactos" href="{{ route('contact.home')}}"><span class="mini-sub-pro">Ver directorio</span></a></li>
                            <li><a title="Añadir contacto" href="{{ route('create.contact') }}"><span class="mini-sub-pro">Agregar contacto</span></a></li>
                              {{--<li><a title="Edit Professor" href="{{ route('create.contact') }}"><span class="mini-sub-pro">Importar contactos</span></a></li> --}}
                                {{--  <li><a title="Professor Profile" href="professor-profile.html"><span class="mini-sub-pro">Professor Profile</span></a></li>  --}}
                            </ul>
                        </li>
                        
                        <li {{ request()->is('home/users*') ? 'class=active': '' }}>
                            <a class="has-arrow" href="#" aria-expanded="false"> <i class="fa fa-user fa-icon"></i>&nbsp;&nbsp;&nbsp;<span class="mini-click-non">Usuarios</span></a>
                            <ul class="submenu-angle" aria-expanded="false">
                                <li {{ request()->is('home/users') ? 'class=active': '' }}><a title="Ver todos los usuarios como agentes, entre otros" href="{{route('users.index')}}"><span class="mini-sub-pro">Ver usuarios</span></a></li>
                                <li><a title="Agregar un nuevo usuario" href="{{route('users')}}"><span class="mini-sub-pro">Agregar usuario</span></a></li>
                            </ul>
                        </li>
                        <li>
                            <a class="" href="{{route('show.all.stock')}}">
                                <i class="fa fa-globe fa-icon"></i>&nbsp;
                                    <span class="mini-click-non ">Bolsa inmobiliaria</span>
                                </a>
                            
                        </li>
                       
                         @role('Admin')
                        <li {{ request()->is('home/settings*') ? 'class=active': '' }}>
                                <a class="has-arrow" href="#" aria-expanded="false"> <i class="fa fa-cogs fa-icon"></i>&nbsp;<span class="mini-click-non">Configuración</span></a>
                                <ul class="submenu-angle" aria-expanded="false">
                                 <li><a title="Mi cuenta" href="{{route('account')}}"><span class="mini-sub-pro">Mi Cuenta</span></a></li>
                                    {{--  <li><a title="Add Professor" href="{{route('setting.web')}}"><span class="mini-sub-pro">Sitio web</span></a></li>  --}}
                                    <li><a title="Planes" href="{{route('view.plans')}}"><span class="mini-sub-pro">Planes</span></a></li>
                                </ul>
                            </li>
                        <li {{ request()->is('/home/website') ? 'class=active': '' }}>
                            <a class="" href="{{ url('home/website') }}" aria-expanded="false"> <i class="fa fa-desktop fa-icon"></i>&nbsp;<span class="mini-click-non"> Sitio web</span></a>
                        </li>
                            @endrole
                        
                    </ul>
                </nav>
            </div>
        </nav>
    </div>
    <!-- End Header menu area -->