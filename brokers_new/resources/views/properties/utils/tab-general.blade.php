<div class="row">
        <div class="form-group col-lg-6 col-md-12 col-sm-12 col-xs-12" style="margin-top: 0;">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 form-group">
                <label for="">Título de la propiedad <span style="color:red;">*</span></label>
                <input name="title" type="text" value="{{old('title',$data->title)}}" maxlength="100" class="form-control"
                placeholder="Ingresa el título de la propiedad" required>    
            </div>

            {{-- Titulo en ingles --}}
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 form-group">
                <label for="">Título de la propiedad en inglés</label>
                <input name="title_en" type="text" value="{{old('title',$data->title_en)}}" maxlength="100" class="form-control"
                placeholder="Ingresa el título de la propiedad en inglés">    
            </div>

            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 form-group" style="margin: 0px 0px 10px 0;">    
                <label for="">Tipo <span style="color:red;">*</span></label> 
                <select name="prop_type_id" class="form-control" required style="z-index:1;">
                    <option value="">Tipo de propiedad</option>
                    @foreach ($types as $item)
                    <option {{$item->id == old('prop_type_id', $property->prop_type_id) ? 'selected' : ''}} value="{{$item->id}}">
                        {{ucwords($item->name)}}</option>
                    @endforeach
                </select>  
            </div>
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6 form-group" style="margin: 0px 0px 10px 0;">
                <label for="">Uso <span style="color:red;">*</span></label> 
                <select name="prop_use_id" class="form-control" required style="z-index:1;">
                    <option value="">Tipo de Uso</option>
                    @foreach ($uses as $item)
                        {{--  @if (ucwords($item->name)!="Ninguno")  --}}
                            <option {{$item->id == old('prop_status_id', $property->prop_use_id) ? 'selected' : ''}} value="{{$item->id}}">
                                {{ucwords($item->name)}}</option>
                        {{--  @else  --}}
                            {{--  @php
                                $item_none=$item;
                            @endphp
                        @endif  --}}
                    @endforeach
                    {{--  <option {{$item_none->id == old('prop_use_id', $property->prop_use_id) ? 'selected' : ''}} value="{{$item_none->id}}">
                        {{ucwords($item_none->name)}}</option>  --}}
                </select>
            </div>
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 form-group" style="margin: 0px 0px 10px 0;">
                <label for="">Estatus <span style="color:red;">*</span></label> 

                <select name="prop_status_id" class="form-control" required style="z-index:1;">
                    <option value="">Elija una opción</option>
        
                    @foreach ($statuses as $item)
                    <option {{$item->id == old('prop_status_id', $property->prop_status_id) ? 'selected' : ''}} value="{{$item->id}}">
                        {{ucwords($item->name)}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group res-mg-t-15 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <label for="">Descripción<span style="color:red;">*</span></label> 
                <textarea required name="description" placeholder="Ingresa una descripción breve" maxlength="1000">{{old('description', $data->description)}}</textarea>
            </div>

            {{-- Descripción en ingles --}}
            <div class="form-group res-mg-t-15 col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <label for="">Descripción en inglés</label> 
                <textarea name="description_en" placeholder="Ingresa una descripción breve en inglés" maxlength="1000">{{old('description', $data->description_en)}}</textarea>
            </div>
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 form-group" style="margin-bottom: auto;margin-top:0" >
                <label for="">Precio <span style="color:red;">*</span></label> 
            </div> 
            <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-6" style="margin: 0px 0px 10px 0;">
                <div class="input-group" style="margin: 0px 0px 10px 0;">
                    <span class="input-group-addon"><strong>$</strong></span>
                    <input type="text" id="price_format"  class="form-control" maxlength="13" placeholder="Ingresa la cantidad">
                    <input type="hidden" id="price" name="price" value="{{old('price', $data->price)}}" min="0" max="9999999999" placeholder="Ingresa la cantidad"
                        class="form-control" required>
                </div>
            </div>
            <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-6" style="margin: 0px 0px 10px 0;">
                <div class="form-group" style="margin: 0px 0px 10px 0;">
                    <select name="currency" class="form-control" required>
                        <option {{"" == old('currency', $data->currency) ? 'selected' : ''}} value="">Moneda</option>
        
                        @foreach (config('app.currency') as $index => $item)
                        <option {{$index == old('currency', $data->currency) ? 'selected' : ''}} value="{{$index}}">{{ucwords($item)}}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>{{--fin primer col-6--}}
        <div class="form-group col-lg-6 col-md-12 col-sm-12 col-xs-12 ">
            <div class="col-md-12">
                <label for="">Ubicación <span style="color:red;">*</span></label>
            </div>
            <div class="col-md-4">
                <select  class="form-control " tabindex="-1" name="state" id="state" style="width:100%;"> 
                    <option value="">Elegir estado</option>
                    @foreach ($states as $state)
                        <option value="{{$state->id}}">{{ $state->name }}</option>
                    @endforeach    
                </select> 
                <span id="error-state" class="hidden text-danger"  style="font-weight: 700;">Este campo es requerido</span>
            </div>
            <div class="col-md-4">
                <select  class="form-control" name="mun"  id="cities" style="width:100%;"></select> 
                <span id="error-mun" class="hidden text-danger" style="font-weight: 700;">Este campo es requerido</span>
            </div>
            <div class="col-md-4">
                <select  class="form-control" name="local_id" id="loc" style="width:100%;"></select>
                    <span id="error-loc" class="hidden text-danger" style="font-weight: 700;">Este campo es requerido</span> 
            </div>
           {{--  <div >  --}}
            <div class="col-md-4" style="margin-top:10px;">
                <input type="text" maxlength="5" class="form-control" name="zipcode" id="zipcode" placeholder="Codigo Postal" value="{{old('zipcode', $data->zipcode)}}" required>
            </div>
            <div class="col-md-4" style="margin-top:10px;">
                <input type="text" maxlength="100" class="form-control" name="exterior" id="exterior" placeholder="Num. exterior" value="{{old('exterior', $data->exterior)}}">
            </div>
            <div class="col-md-4" style="margin-top:10px;">
                <input type="text" maxlength="100" class="form-control" name="interior" id="interior" placeholder="Num. interior" value="{{old('interior', $data->interior)}}">
            </div>
           {{--  </div>  --}}
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:10px;">
                <div id="map" style="width: 100%;height: 300px;"></div>
            </div>
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:10px;">
                <label for="">Calles </label><span style="color:red;"> *</span>
                <input type="text" maxlength="150" class="form-control" name="address" id="address" placeholder="Escriba las calles" value="{{old('address', $data->address)}}" required>
            </div>
        </div>{{--fin segundo col-6--}}
        @if (!$edit)
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center " style="margin-top:55px;">
                <button type="button" id="next" class="btn btn-primary waves-effect waves-light">Siguiente &nbsp; <i class="fa fa-chevron-right"></i></button>
            </div>{{--fin button next--}}
        @endif
        </div>
        @push('scripts')
            <script src="{{ asset('admin/js/select2/select2.full.min.js') }}"></script>
            <script src="{{ asset('admin/js/select2/select2-active.js') }}"></script>
            <script src="{{ asset('js/location-control.js?version=1') }}"></script>
            <script src="{{ asset('admin/js/input-mask/jasny-bootstrap.min.js') }}"></script>

            <script>
            //Establecer el precio formateado en el input price_format donde se ingresa al cantidad
            function setFormatPrice(input_val){
                var num= input_val.replace(/\,/g,'');
                if(num.length>0)
                {
                    $("#price").val(num); //Input escondido 
                    var format =num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    $("#price_format").val( format );
                }
            }
            //Establecer el precio formateado al editar o en una validacion
            setFormatPrice("{{old('price', $data->price)}}")
            //Establecer el precio formateado cuando se esta ingresando
            $('#price_format').keyup(function(e) {
                setFormatPrice($(this).val());
            });
            //Validacion de solo ingresar numeros y un punto para formatearlo
            $('#price_format').keypress(function(e) {
                if (e.which != 8 && e.which != 0 && e.which!=46 && (e.which < 48 || e.which > 57)) 
                { return false; }
                else
                {
                    if(e.which==44)
                    {
                        return false;
                    }
                    else if(e.which==46)
                    {
                        if(this.value.split('.').length>=2)
                        {
                            return false;
                        }
                    }
                }  
               
            });
            </script>
        @endpush

        @push('styles')
            <link rel="stylesheet" href="{{ asset('admin/css/select2/select2.min.css') }}">
        @endpush
        
        
        
        
        