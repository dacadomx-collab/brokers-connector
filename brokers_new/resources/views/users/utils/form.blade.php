<div class="row">
    <div class="col-md-4">
        <div class="row">
            <div class="col-md-12">
                <img id="preview-image" style="width: 18em;margin: 0 auto;height: 18em;display: block;border-radius: 50%;object-fit: cover;" src="{{ !$edit ? "http://www.rafacademy.com/wp-content/uploads/2017/03/user-default.png" : $data->FotoAvatar }}"
                    alt="">
            </div>
            <div class="col-md-12 text-center" style="margin-top:20px">
                <label style="background: #303030;color: #fff;border: 1px solid #333;" class="btn"
                    for="input-image">Subir imagen</label>
                <input type="file" name="file" id="input-image" accept="image/*" style="opacity:0; position:absolute;">
            </div>
        </div>
    </div>
    <div class="col-md-8">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('full_name')) has-error @endif">
                        <label for="full_name">Nombre(s) <span style="color:red;">*</span></label>
                        <input required type="text" class="form-control" maxlength="255" name="full_name"  autocomplete="off"
                            value="{{old('full_name',$data->full_name)}}" placeholder="Ingresar Nombre(s)">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('last_name')) has-error @endif">
                        <label for="surname">Apellido(s) <span style="color:red;">*</span></label>
                        <input required type="text" class="form-control" maxlength="255" name="last_name"  autocomplete="off"
                            value="{{old('last_name', $data->last_name)}}" placeholder="Apellido(s)">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('title')) has-error @endif">
                        <label for="surname">Título</label>
                        <input type="text" class="form-control" maxlength="255" name="title" autocomplete="off"
                            value="{{old('title', $data->title)}}" placeholder="Ingresar el título">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('phone')) has-error @endif">
                        <label for="">Teléfono</label>
                        <input type="text" class="form-control phone-input" maxlength="13" name="phone"  autocomplete="off"
                            value="{{old('phone', $data->phone)}}" placeholder="Ingresar un teléfono">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('email')) has-error @endif">
                        <label for=""> Correo electrónico <span style="color:red;">*</span></label>
                        <input required type="email" class="form-control" maxlength="255" name="email" autocomplete="off"
                            value="{{old('email', $data->email)}}" placeholder="Ingresa un correo electrónico valido">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('rol')) has-error @endif">
                        <label for="rol">Tipo:</label>
                        <select name="user_a" class="form-control" id="rol">
                        @if ($user->hasrole("Admin") && $edit)
                            {{-- No editar su rol a propietario --}}
                            <option value="{{$user->Roles()->first()->id}}">{{$user->Roles()->first()->display_name}}</option>
                        @else
                            @foreach ($roles as $value)
                            <option value="{{$value->name}}">{{$value->display_name}}</option>
                            @endforeach
                        @endif
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group  @if($errors->has('password')) has-error @endif">
                        <label for="">Contraseña {!! !$edit ? '<span style="color:red;">*</span>' : ""!!} </label>
                        <input id="pass" {{ !$edit ? "required" : "" }} type="password" class="form-control" minlength="8" autocomplete="off"
                            name="password" placeholder="Ingresa una contraseña">
                        @if ($edit)
                        <span class="info">Ingresar en caso de nueva contraseña</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group @if($errors->has('password_confirmation')) has-error @endif">
                        <label for="">Confirmar contraseña {!! !$edit ? '<span style="color:red;">*</span>':
                            ""!!}</label>
                        <input {{ !$edit ? "required" : "" }} type="password" class="form-control" minlength="8" autocomplete="off"
                            name="password_confirmation" placeholder="Ingresar una confirmación de contraseña">
                    </div>
                </div>
            </div>
    </div>
</div>
{{--  <hr>

<div class="col-md-12" style="margin-bottom:10px;">

    </div>
   
        <div class="col-md-6 form-gorup">
            
            
            <div class="row">
                <div class="form-group col-md-6 @if($errors->has('full_name')) has-error @endif">
                    <label for="full_name">Nombres <span style="color:red;">*</span></label>
                    <input required type="text" class="form-control" maxlength="255" name="full_name" value="{{old('full_name',$data->full_name)}}"
placeholder="Ingresar Nombre(s)">
</div>


<div class="form-group col-md-6 @if($errors->has('last_name1')) has-error @endif">
    <label for="surname">Apellido(s) <span style="color:red;">*</span></label>
    <input required type="text" class="form-control" maxlength="255" name="last_name"
        value="{{old('last_name', $data->last_name)}}" placeholder="Apellido(s)">
</div>


</div>

<div class="row">
    <div class="form-group col-md-6 @if($errors->has('title')) has-error @endif">
        <label for="surname">Título <span style="color:red;">*</span></label>
        <input required type="text" class="form-control" maxlength="255" name="title"
            value="{{old('title', $data->title)}}" placeholder="Ingrear el título">
    </div>

    <div class="form-group col-md-6 @if($errors->has('phone')) has-error @endif">
        <label for="">Teléfono <span style="color:red;">*</span></label>
        <input required type="text" class="form-control phone-input" maxlength="10" name="phone"
            value="{{old('phone', $data->phone)}}" placeholder="Ingrese un teléfono">
    </div>

</div>


</div>

<div class="col-md-6">

    <div class="row">


        <div class="form-group col-md-6 @if($errors->has('email')) has-error @endif">
            <label for=""> Correo electroníco <span style="color:red;">*</span></label>
            <input required type="text" class="form-control" maxlength="255" email name="email"
                value="{{old('email', $data->email)}}" placeholder="Ingresa un correo electroníco valido">
        </div>


        <div class="form-group col-md-6 @if($errors->has('rol')) has-error @endif">
            <label for="rol">Tipo:</label>
            <select name="user_a" class="form-control" id="rol">

                @foreach ($roles as $value)
                <option value="{{$value->name}}">{{$value->display_name}}</option>
                @endforeach
            </select>
        </div>
    </div>





    @if(old('user_a', $data->user_a)!=null)
    <script>
        document.getElementById("rol").value = "{{old('user_a', $data->user_a)}}";

    </script>
    @endif
    <div class="row">
        <div class="form-group col-md-6 @if($errors->has('password')) has-error @endif">
            <label for="">Contraseña {!! !$edit ? '<span style="color:red;">*</span>' : ""!!} </label>
            <input {{ !$edit ? "required" : "" }} type="password" class="form-control" maxlength="10" name="password"
                placeholder="Ingresa una contraseña">
            @if ($edit)
            <span class="info">Ingresar en caso de nueva contraseña</span>
            @endif
        </div>


        <div class="form-group col-md-6 @if($errors->has('password_confirmation')) has-error @endif">
            <label for="">Confirmar contraseña {!! !$edit ? '<span style="color:red;">*</span>': ""!!}</label>
            <input {{ !$edit ? "required" : "" }} type="password" class="form-control" maxlength="10"
                name="password_confirmation" placeholder="Ingresar una confirmación de contraseña">
        </div>

    </div>



</div> --}}

<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="margin-top:35px;">
    <a href="{{route('users.index')}}" role="button" id="btn-cancel" class="btn btn-default">
        Cancelar &nbsp;<i class="fa fa-times"></i><a>
    </button>&nbsp;
    <button type="button" id="btn-save"  class="btn btn-primary waves-effect waves-light">
        Guardar &nbsp;<i class="fa fa-check"></i>
    </button>
</div>
