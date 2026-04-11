<form action="{{$url}}" method="get">
<div class="container-fluid">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div style="padding-top: 20px;background: #fff;padding-left: 20px;padding-right: 20px;margin-bottom: 15px;padding-bottom: 20px;">
                <div class="row">
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-12">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="status_select">Operación</label>
                                <select class="form-control" name="status" id="status_select"
                                    style="width:100%;">
                                    <option value="">Elegir</option>
                                    @foreach ($statuses as $status)
                                    <option
                                        @isset($old_inputs, $old_inputs['status']){{$old_inputs['status']==$status->id ? "selected" :  ""}}@endisset
                                        value="{{$status->id}}">{{ucfirst($status->name)}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="type_select">Propiedad</label>
                                <select data-placeholder="Escoge uno o varios" class="chosen-select form-control" name="type[]" multiple="" tabindex="-1" style="width:100%;">
                                    <option value="">Select</option>
                                    @foreach ($types as $type)
                                    <option
                                        @isset($old_inputs, $old_inputs['type']) @if(in_array($type->id, $old_inputs['type'])) selected @endif @endisset
                                        value="{{$type->id}}">{{ucfirst($type->name)}}</option>
                                    @endforeach
                                </select>
                            </div>
    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="price">Precio min.</label><br>
                                <input type="text" class="form-control price" placeholder="Min" name="price_min"
                                    id="price" max="9999999999" style="width:100%;"
                                    value="@isset($old_inputs, $old_inputs['price_min']){{$old_inputs['price_min']}}@endisset">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="price">Precio max.</label><br>
                                <input type="text" class="form-control price" placeholder="Max" name="price_max"
                                    max="9999999999" style="width:100%;"
                                    value="@isset($old_inputs, $old_inputs['price_max']){{$old_inputs['price_max']}}@endisset">
                            </div>
    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="state">Estado</label>
                                <select  class="form-control" name="state"  id="states" style="width:100%;"></select> 
                            </div>

                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="city">Ciudad</label>
                                <select  class="form-control" name="city"  id="cities" style="width:100%;"></select> 
                            </div>
                            
                        </div>

                        <div class="row ">
                            <div class="col-lg-2">
                                <button id="button-advanced" type="button" class="btn btn-default"
                                    style="margin-top:25px; width:-webkit-fill-available">Más filtros</button>
                            </div>
                           
                        </div>
                      
                        <div class="row" id="advanced"
                            style="display:@isset($old_inputs){{$btn_more_display ? 'block' : 'none' }}@else none @endisset; margin-top: 15px;">
    
                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                <label for="search">Buscar por nombre</label>
                                <input type="text" class="form-control" placeholder="Ingresa el nombre"
                                    name="search" id="search" style="width:100%;"
                                    value="@isset($old_inputs, $old_inputs['search']){{$old_inputs['search']}}@endisset">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="parking">Estacionamientos</label>
                                <input type="number" class="form-control" placeholder="Cantidad" min="0"
                                    name="parking" id="parking" style="width:100%;"
                                    value="@isset($old_inputs, $old_inputs['parking']){{$old_inputs['parking']}}@endisset">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="baths">Baños</label>
                                <input type="number" class="form-control" name="baths" placeholder="Cantidad"
                                    min="0" id="baths" style="width:100%;"
                                    value="@isset($old_inputs, $old_inputs['baths']){{$old_inputs['baths']}}@endisset">
                            </div>
    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="room">Habitaciones</label>
                                <input type="number" class="form-control" name="rooms" placeholder="Cantidad"
                                    min="0" id="room" style="width:100%;"
                                    value="@isset($old_inputs, $old_inputs['rooms']){{$old_inputs['rooms']}}@endisset">
                            </div>
                        </div>
    
                        <div class="row" style="margin-top:10px;">
                            
    
    
                        </div>
    
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                        {{-- <div class="col-md-12 text-center"> --}}
                        <button class="btn btn-primary" style="width:-webkit-fill-available; margin-top: 25px;">
                            <i class="fa fa-search" aria-hidden="true"></i>
                        </button>
                        @isset($old_inputs)
                        <a class="text-center" href="{{$url_clean}}">Limpiar campos</a>
                        @endisset
                    <br><br>
                        {{-- </div> --}}
                    </div>
                   
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="row">
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-6 pull-right">
                                @if (Session::has('view-stock'))

                                    <a id="view" style="min-height:40px;"
                                        href="/stock/change-view?value={{Session::get('view-stock') ? '0' : '1'}}"
                                        data-toggle="tooltip" title="Cambiar vista" class="btn btn-custon-four">
                                        <i style="vertical-align: middle;"
                                            class="fa {{Session::get('view-stock') ? 'fa-th-large' : 'fa-list'}}"></i></a>
                                @else
                                    <a id="view" style="min-height:40px;" href="{{ url('stock/change-view') }}?value=1"
                                        data-toggle="tooltip" title="Cambiar vista" class="btn btn-custon-four">
                                        <i style="vertical-align: middle;" class="fa fa-list"></i></a>
                                @endif
                            </div>
                            

                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6 pull-right">
                    
                                <select class="form-control" name="properties_show" onchange="this.form.submit()">

                                  
                                    <option @isset($old_inputs){{$old_inputs['properties_show']==1 ? "selected" : ""}}@endisset value="1">Bolsa Inmobliliaria</option>                         
                                    

                                    @if ($service_adquired->isAspiOrAmpi())
                                        <option @isset($old_inputs){{$old_inputs['properties_show']==2 ? "selected" : ""}}@endisset value="2">Bolsa ASPI</option>
                                        <option @isset($old_inputs){{$old_inputs['properties_show']==3 ? "selected" : ""}}@endisset value="3">Bolsa AMPI</option>
                                    @endif

                                 
                                        <option @isset($old_inputs){{$old_inputs['properties_show']==4 ? "selected" : ""}}@endisset value="4">Mis propiedades</option>
                                  
                                        
                                </select>
                            </div>
                          
                    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-6 pull-right">
                                    
                                <select class="form-control" name="order" onchange="this.form.submit()">
                                    <option value="">Ordenar por</option>
                                    {{--  @foreach ($statuses as $status)  --}}
                                    <option @isset($old_inputs, $old_inputs['order'] ){{$old_inputs['order']==1 ? "selected" : ""}}@endisset value="1">Precio mas bajo</option>
                                    <option @isset($old_inputs, $old_inputs['order'] ){{$old_inputs['order']==2 ? "selected" : ""}}@endisset value="2">Precio mas alto</option>
                                    <option @isset($old_inputs, $old_inputs['order'] ){{$old_inputs['order']==3 ? "selected" : ""}}@endisset value="3">Titulo ascendente (A-Z)</option>
                                    <option @isset($old_inputs, $old_inputs['order'] ){{$old_inputs['order']==4 ? "selected" : ""}}@endisset value="4">Titulo descendente (Z-A)</option>
                                    <option @isset($old_inputs, $old_inputs['order'] ){{$old_inputs['order']==5 ? "selected" : ""}}@endisset value="5">Mas recientes</option>
                                    <option @isset($old_inputs, $old_inputs['order'] ){{$old_inputs['order']==6 ? "selected" : ""}}@endisset value="6">Mas antiguos</option>
                                    {{--  @endforeach  --}}
                                </select>

                            </div>

                           
                        </div>
                    </div>
                </div>
    
           
        </div>
    </div>
</div>
    
    </form>