@extends('layouts.app')
@section('title','Mis facturas')

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span></li>
<li><a href="{{ url('home/invoices') }}">Mis facturas</a> <span class="bread-slash">/</span></li>
<li><span class="bread-blod">Factura # {{$invoice->id}}</span>
</li>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
{{--  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css?version={{ config('app.version')}}/3.7.2/animate.min.css?version={{ config('app.version')}}">  --}}

{{--  <link rel="stylesheet" href="{{ asset('css/card.css') }}">  --}}

@endpush

@section('content')

<div class="product-status mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-status-wrap drp-lst">
                    <h2>Factura # {{$invoice->id}}</h2>
                        <div class="add-product">
                          
                                <a  class="btn btn-success" style="background-color: green;" disabled>Pagado</a>
                            
                           
                           
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="product-status mg-b-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="product-status-wrap drp-lst">
                        <div class="row">
                            <div class="col-xs-6">
                                    <h4>Para:</h4>
                                    <p>{{$company->owner_user->name}}</p>
                                    <p>{{$company->name}}</p>
                                    <p>{{$company->address}}</p>
                                    <p>{{$company->rfc}}</p>
                                    
                            </div>
                            <div class="col-xs-6">
                                    <div class="text-right">
                                    <p>Fecha de pago: {{$invoice->m_invoice_date}}</p>
                                            <p>Fecha de vencimiento: {{$invoice->m_due_date}}</p>
                                            <p> <b> Estatus: </b>Pagado </p>
                                        </div>
                                </div>
                        </div>
                        <div class="row">

                            <div class="col-xs-12">
                                    <div class="asset-inner ">
                                        <table class="table table-hover">
                                            <tbody>
                                                <tr style="background-color:gray; ">
                                                    <th style="color:white;">Nombre</th>
                                                    <th style="color:white;">Cantidad</th>
                                                    <th style="text-align: right; color:white;">Precio</th>
                                                </tr>
                                                <tr>
                                                    <td>{{$invoice->name}}</td>
                                                    <td>1</td>
                                                    <td style="text-align: right;">${{number_format($invoice->cost_package)}} MXN</td>
                                                </tr>
                                                @if ($invoice->users)
                                                <tr>
                                                    <td>Usuario extra</td>
                                                    <td>{{$invoice->users}}</td>
                                                    <td style="text-align: right;">${{number_format($invoice->users * $invoice->cost_user)}} MXN</td>
                                                </tr>
                                                @endif
                                                  
                                            </tbody>
                                        </table>
                                        <hr >
                                        <p style="text-align: right;">Subtotal: ${{ ($invoice->cost_package + ($invoice->cost_user * $invoice->users)) }} MXN</p>
                                        <p style="text-align: right;">IVA 16%: ${{ ($invoice->cost_package + ($invoice->cost_user * $invoice->users)) * 0.16 }} MXN</p>
                                        <h3 style="text-align: right;">Total: ${{ number_format($invoice->total) }} MXN</h3>
                                         
                                        </div>
                            </div>
                        </div>
                      
                  
                    </div>
                </div>
            </div>
        </div>
    </div>
  {{--  @include('invoices.payment_method.card')
  @include('invoices.payment_method.index')  --}}
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>



@if (Session::has('error_code'))
<script>
        Lobibox.notify('error', {
            title: 'Error',
            verticalOffset: 5,
            position: 'top right',
            height: 'auto',      
            showClass: 'fadeInDown',
            hideClass: 'fadeUpDown',
            msg: "{{ session('error_m') }}"
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
        msg: "EL PAGO SE REALIZÓ CON ÉXITO"
    });
  
</script>
@endif
@endpush