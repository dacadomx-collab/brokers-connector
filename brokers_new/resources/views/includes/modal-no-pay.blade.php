<div id="company_activate" class="modal modal-edu-general default-popup-PrimaryModal fade opened" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- <div class="modal-close-area modal-close-df">
                <a class="close" data-dismiss="modal" href="#"><i class="fa fa-close"></i></a>
            </div> --}}
            <div class="modal-body">
                    <img class="main-logo img-thumbnail" src="{{ asset('img/logo/logo-recortado.png') }}" alt="" style="margin-bottom:20px;"/>
                @if(Auth::user()->company!=null)
                    
                        <h3>Lamentamos que no continúe con Brokers Connector, ha sido desactivado por un problema con el pago. <br><br>Si esto es un error comuniquese a 6121225174.</h3>
                 
                @endif
                  
            </div>
            <div class="modal-footer text-center" style="width:100%;">
              @role('Agent')
               <div class="row">
                   <div class="col-md-12 text-center">
                    <a style="cursor:pointer;" onclick="event.preventDefault; document.getElementById('logout-form').submit();">
                        <i class="fa fa-power-off" ></i><span class="edu-icon edu-locked author-log-ic"></span>  Cerrar sesión
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                   </div>
               </div>
               @endrole
               
              
               <div class="row">
                   <div class="col-md-12 text-center">
                    <a href="{{ url('home/invoices') }}"> Pagar </a>
             
                   </div>
               </div>
              
            </div>
        </div>
    </div>
</div>