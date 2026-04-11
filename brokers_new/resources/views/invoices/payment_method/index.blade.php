<div id="modal_payment_method" class="modal modal-edu-general default-popup-PrimaryModal fade" role="dialog" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-close-area modal-close-df">
                <a class="close" data-dismiss="modal" href="#"><i class="fa fa-close"></i></a>
            </div>
            <div class="modal-body">
                    <div class="payment-title">
                        <br>
                            <h1>Selecciona el método de pago</h1>
                    </div>
                        <div class="container ">
                           
                            <button  id="tarjeta" data-toggle="modal"  data-target="#PrimaryModalalert" type="button">Tarjeta (Cargo Directo)</button>
                            <a  href="{{ route('invoices.paynet', ['invoice'=>$invoice->id]) }}" >Paynet(Efectivo)</a>
                            <a  href="{{ route('invoices.spei', ['invoice'=>$invoice->id]) }}" >SPEI - Cargo directo</a>
                            
                        </div>
                        
                    
    </div>
</div>
</div>