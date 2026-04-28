@extends('layouts.app')
@section('title','Facturas')

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Mis facturas</span>

</li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
{{--  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css?version={{ config('app.version')}}/3.7.2/animate.min.css?version={{ config('app.version')}}">  --}}

<link rel="stylesheet" href="{{ asset('css/card.css') }}">

@endpush

@section('content')
<div class="static-table-area">
  <div class="container-fluid">
      <div class="row">

        @if (auth()->user()->company->has_to_pay)
        <div class="col-md-12">
            <div class="hpanel widget-int-shape responsive-mg-b-30">
                <div class="panel-body">
                    <div class="text-center content-box">
                        <h2 class="m-b-xs" style="margin-bottom: 15px;">Pago pendiente</h2>
                      
                        <div class="m icon-box" style="margin-bottom: 15px;">
                          <img src="/images/pay.svg" width="150" alt="">
                        </div>
                        <p class="small mg-t-box" style="margin: 0; color: #5d5d5d;">
                           Con tu pago nos ayudas a seguir creciendo, paga antes de que expire o bien si ya expiró tu servicio
                        </p>
                        <button class="btn btn-success btn-md widget-btn-1 btn-sm mg-t-box" style="font-size: large;
                        padding: 12px 18px;" data-toggle="modal" data-target="#PrimaryModalalert">Pagar ahora</button>
                    </div>
                </div>
            </div>
            <hr>
        </div>
        @endif

          <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
              <div class="sparkline8-list">
                  <div class="sparkline8-hd">
                      <div class="main-sparkline8-hd">
                          <h1></h1>
                      </div>
                  </div>
                  <div class="sparkline8-graph">
                     
                      <div class="static-table-list table-responsive">
                          <table class="table table-bordered">
                              <thead>
                                  <tr>
                                      <th>Factura #</th>
                                      <th>Total</th>
                                      <th>Status</th>
                                      <th>Fecha de pago</th>
                                      <th>Fecha de vencimiento</th>
                                      <th>Detalles</th>
                                  </tr>
                              </thead>
                              <tbody>


                           
                                  @foreach($invoices as $invoice)
                                    <tr>
                                        <td><a href="{{ url('home/invoices/'.$invoice->id) }}">{{$invoice->id}}</a></td>
                                        <td>$ {{$invoice->total}} MXN</td>
                                        <td> @if($invoice->status)  <button class="btn btn-success btn-xs">Pagado </button> @else <button class="btn btn-warning btn-xs">Pendiente de pago</button>  @endif</td>
                                        <td>{{$invoice->payday->format('d-m-Y')}}</td>
                                        <td>{{$invoice->m_due_date}}</td>
                                        <td> <a href="{{ url('home/invoices/'.$invoice->id) }}" class="btn btn-default">Ver factura</a> </td>
                                    </tr>
                                  @endforeach
                              </tbody>
                          </table>
                          <div align="center">
                            {{ $invoices->links() }}
                        </div>
                      </div>
                  </div>
              </div>
          </div>
   
      </div>

  </div>
</div>


@include('invoices.payment_method.card')

@endsection

@push('scripts')
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/imask/3.4.0/imask.min.js"></script>
    <script src="{{ asset('js/card.js') }}"></script>
    <script type="text/javascript" src="https://openpay.s3.amazonaws.com/openpay.v1.min.js"></script>
    <script type='text/javascript' src="https://openpay.s3.amazonaws.com/openpay-data.v1.min.js"></script>  

    <script>
        $(document).ready(function() {

            OpenPay.setId("{{env('OPENPAY_ID')}}");
            OpenPay.setApiKey("{{env('OPENPAY_KEY_PUBLIC')}}");
            OpenPay.setSandboxMode({{ env('OPENPAY_PRODUCTION', false) ? 'false' : 'true' }});
            //Se genera el id de dispositivo
            var deviceSessionId = OpenPay.deviceData.setup("payment-form", "deviceIdHiddenFieldName");
            
            $('#pay-button').on('click', function(event) {
                event.preventDefault();
                $("#pay-button").prop("disabled", true);
                OpenPay.token.extractFormAndCreate('payment-form', sucess_callbak, error_callbak);                
            });
        
            var sucess_callbak = function(response) {
            var token_id = response.data.id;
            $('#token_id').val(token_id);
            $('#payment-form').submit();
            };
        
            var error_callbak = function(response) {
                var desc = response.data.description != undefined ? response.data.description : response.message;
                //alert(response.data.error_code);
                error = "Error desconocido."
                if(response.data.error_code== 1001)
                {
                    if('cvv2 length must be 3 digits' == desc)
                    {
                        error = "El codigo de seguridad no es correcto.";
                    }else if( "card_number length is invalid" == desc)
                    {
                        error = "El numero de tarjeta no es valido."
                    }else{
                        error = "Todos los campos son necesarios."
                    }
                
                }
                else if(response.data.error_code == 2005)
                {
                    error ="La fecha de vencimiento ya paso.";
                }
        
                else if(response.data.error_code== 2006)
                {
                    error ="El codigo de seguridad es requerido.";
                }
                else if(response.data.error_code== 2004)
                {
                    error ="El numero de tarjeta no es correcto.";
                }
                else{
                    error ="ERROR " + desc;
                }
                Lobibox.notify('error', {
                    title: 'Error',
                    verticalOffset: 5,
                    position: 'top right',
                    height: 'auto',      
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: error
            });
                $("#pay-button").prop("disabled", false);
            };
        
        });      
        
        
        
    </script>

        @if (Session::has('error'))
    <script>
            Lobibox.notify('error', {
                title: 'Error',
                verticalOffset: 5,
                position: 'top right',
                height: 'auto',      
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: "{{ session('error') }}"
        });
    </script>

    @endif

    @if (Session::has('success'))
    <script>
        Lobibox.notify('success', {
            title: 'Información actualizada',
            position: 'top right',
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "{{ session('success') }}"
        });
    </script>
    @endif
@endpush