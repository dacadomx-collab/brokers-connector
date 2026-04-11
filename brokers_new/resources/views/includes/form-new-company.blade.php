<div id="company_information" class="modal modal-edu-general default-popup-PrimaryModal fade opened" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            {{-- <div class="modal-close-area modal-close-df">
                <a class="close" data-dismiss="modal" href="#"><i class="fa fa-close"></i></a>
            </div> --}}
            <form action="{{route('store.company')}}" method="post" enctype="multipart/form-data" id="form-control">
            <div class="modal-body" style="padding:15px;">
                    <img class="main-logo img-thumbnail" src="{{ asset('img/logo/logo-recortado.png') }}" alt="" style="margin-bottom:20px;"/>
                    <h3>Bienvenido a Brokers Connector</h3>
                    <h5>Para continuar, ingresa la información de tu empresa:</h5>
                        @csrf
                        <br>
                        <div class="col-md-4">
                            <label for="">Logo:</label>
                            <img id="logo-preview" src="{{ asset('images/no-logo.jpg') }}" style="width:100%; max-height:150px;object-fit: contain;">
                            <br><br>
                            <label style="background: #303030;color: #fff;border: 1px solid #333;" class="btn"
                            for="input-image">Elegir imagen</label>
                            <input type="file" name="file" id="input-image" accept="image/*" style="opacity:0; position:absolute;">
            
                           <br>
                            <span class="info">Dejar vacío si no se requiere subir logo</span>
                                
                           
                        </div>
                        <div class="col-md-8">
                            <div class="form-group @if($errors->has('name')) has-error @endif">
                                
                                <input type="text" class="form-control" required maxlength="255"  name="name" id="name" placeholder="Nombre de la empresa"
                                value="@if(Auth::user()->company!=null){{old('name', Auth::user()->company->name)}}@else{{old('name')}}@endif"  >
                            </div>
                            <div class="form-group @if($errors->has('rfc')) has-error @endif">
                                
                                <input type="text" class="form-control" required maxlength="255" name="rfc" id="rfc" placeholder="RFC"
                                    value="{{old('rfc')}}">                                    
                            </div>
                            <div class="form-group @if($errors->has('phone')) has-error @endif">
                                
                                <input type="text" class="form-control" required maxlength="10" name="phone" id="phone"  placeholder="Teléfono"
                                    value="@if(Auth::user()->company!=null){{old('phone',Auth::user()->company->phone)}}@else{{old('phone')}}@endif">
                            </div>
                            <div class="form-group @if($errors->has('address')) has-error @endif">
                                
                                <input type="address" class="form-control" required maxlength="255" name="address" id="address" placeholder="Dirección fiscal"
                                    value="@if(Auth::user()->company!=null){{ old('address', Auth::user()->company->address)}}@else{{old('address')}}@endif">
                            </div>
                            
                            <div class="form-group @if($errors->has('colony')) has-error @endif">
                                
                                <input type="text" class="form-control" required maxlength="255" name="colony" id="colony" placeholder="Colonia"
                                    value="{{old('colony')}}">
                            </div>
                            
                            <div class="form-group @if($errors->has('zipcode')) has-error @endif">
                                <input type="number" class="form-control" required maxlength="10" min="0" name="zipcode" id="zipcode" placeholder="Codigo Postal"
                                 value="{{old('zipcode')}}">
                            </div>
                            <div class="form-group @if($errors->has('email')) has-error @endif">
                                <input type="email" class="form-control" required maxlength="255" email name="email" id="email" placeholder="Correo electroníco"
                                    value="@if(Auth::user()->company!=null){{ old('email', Auth::user()->company->email) }} @else {{old('email', Auth::user()->email)}} @endif">
                            </div>
                        </div>


                        <br>
                        <div class="col-md-12">
                            
                            <label >Elige un nombre para tu sitio web <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="Esta es la dirección web donde podras visualizar tu pagina web creada con Brokers Connector, si deseas conectar tu propio dominio o adquirir uno por favor escribenos a sistemas@brokersconnector.com"></i></label>
                            
                            <div class="form-group @if($errors->has('dominio')) has-error @endif">
                                <div class="input-group">
                                    <input type="text" class="form-control" required pattern="^[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]$" maxlength="35" name="dominio" id="dominio" placeholder="Escribe un nombre para tu direccion web"
                                    value="@if(Auth::user()->company!=null){{old('dominio', Auth::user()->company->dominio)}}@else{{old('dominio')}}@endif">
                                    <span class="input-group-addon">.brokersconnector.com</span>    
                                   
                                </div>
                            </div>

                            
                        </div>
                        <div>
                           <div class="col-md-12">
                                <hr>
                           </div>

                            <h4>Selecciona tu plan<span class="pull-right" style="font-size:small;font-weight: inherit;" ><a target="_blank" href="https://brokersconnector.com/#price">Mas información</a></span>
                            </h4>
                            <div class="row">
                                <div class="col-md-4">
                                <label class="">
                                    <p>Single</p>
                                    {{-- img flaticon desactivado: ERR_NAME_NOT_RESOLVED --}}
                                    <br><input type="radio" value="1" name="package" {{ old('package') == 1 ? 'checked' : '' }} required>
                                </label>
                                </div>
                                <div class="col-md-4">
                                <label class="">
                                    <p>Corporation</p>
                                    {{-- img flaticon desactivado: ERR_NAME_NOT_RESOLVED --}}
                                    <br><input type="radio" value="2" name="package" {{ old('package') == 2 ? 'checked' : '' }} required>
                                </label>
                                </div>
                                <div class="col-md-4">
                                <label class="">
                                    <p>General</p>
                                    {{-- img flaticon desactivado: ERR_NAME_NOT_RESOLVED --}}
                                    <br><input type="radio" value="3" name="package" {{ old('package') == 3 ? 'checked' : '' }} required>
                                </label>
                                </div>
                            </div>
                            {{-- <label class="radio-inline"><input type="radio" value="3" name="package" required>General</label> --}}
                            {{-- @foreach ($services as $service)
                                @if($service->id != 4)  <!-- 4 es el usuario extra en la base de datos-->
                                    <label class="radio-inline"><input type="radio" value="{{$service->id}}" name="package" required>{{$service->service}}</label>
                                @endif
                            @endforeach --}}
                        </div>
                    
            </div>
            <div class="modal-footer text-center" style="width:100%;">
              
               <div class="row">
                   <div class="col-md-12 text-center">
                       <button type="buttton" class="btn btn-danger  btn-lg btn-save" style="width:50%;">Continuar</button>
                   </div>
               </div>
            </div>
        </form>  
        </div>
    </div>
</div>