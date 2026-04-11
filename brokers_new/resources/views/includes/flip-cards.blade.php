<div class="flip-cards-row">

    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-home fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="{{ url('properties/index') }}">Propiedades</a></p>
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
                    <p class="text"><a href="{{ url('home/website') }}">Sitio Web</a></p>
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
                    <p class="text"><a href="{{ route('show.all.stock') }}">Bolsa inmobiliaria</a></p>
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
                    <p class="text"><a href="{{ route('contact.home') }}">Contactos</a></p>
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
                    <p class="text"><a href="{{ route('users.index') }}">Usuarios</a></p>
                </div>
            </div>
        </div>
    </div>

    @role('Admin')
    <div class="flip-col">
        <div class="flip-card" tabIndex="0">
            <div class="flip-card-inner">
                <div class="flip-card-front">
                    <i class="menu-icon fa fa-cogs fc-icon"></i>
                </div>
                <div class="flip-card-back">
                    <p class="text"><a href="{{ route('setting.web') }}">Configuración</a></p>
                </div>
            </div>
        </div>
    </div>
    @endrole

</div>
