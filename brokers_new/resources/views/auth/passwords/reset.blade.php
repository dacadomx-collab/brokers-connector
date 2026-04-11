

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
   
   <!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="{{ asset('img/logo/img-logo-brokers2.png') }}"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/vendor/bootstrap/css/bootstrap.min.css') }}">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/fonts/font-awesome-4.7.0/css/font-awesome.min.css') }}">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/vendor/animate/animate.css') }}">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/vendor/css-hamburgers/hamburgers.min.css') }}">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/vendor/select2/select2.min.css') }}">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/css/util.css') }}">
	<link rel="stylesheet" type="text/css" href="{{ asset('login-broker/css/main.css') }}">
<!--===============================================================================================-->
   <style>
      @media (max-width: 576px) {
    .wrapps {
        padding: 40px 25px 33px 25px !important;
    }
   
}

@media (max-width: 768px) {
    .wrapps {
        padding: 100px 80px 33px 80px;
    }
}
@media (max-width: 992px) {
    .wrapps {
        padding: 177px 90px 33px 85px;
    }
}

        html,body { height: 100%; }

body{
	display: -ms-flexbox;
	display: -webkit-box;
	display: flex
    -ms-flex-align: center;
	-ms-flex-align: center; 
	-ms-flex-pack: center;
	-webkit-box-align: center;
	align-items: center;
	-webkit-box-pack: center;
	justify-content: center;
	background-color: #4baaca;
}

form{
	padding-top: 10px;
	font-size: 14px;
	margin-top: 30px;
    
}

.card-title{ font-weight:300; }

.btn{
	font-size: 14px;
	margin-top:20px;
}

.login-form{ 
	width:320px;
	margin:20px;
    
}

.sign-up{
	text-align:center;
	padding:20px 0 0;
}

span{
	font-size:14px;
}
.login100-form-btn{
    background-color:#3077b7;
    font-family: Poppins-Regular;
    font-size: 15px;
    line-height: 1.5;
    color: #fff;
    width: 100%;
    height: 50px;
    border-radius: 25px;
    border: none;
    background: #57b846;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0 25px;
    -webkit-transition: all 0.4s;
    -o-transition: all 0.4s;
    -moz-transition: all 0.4s;
    transition: all 0.4s;
}
    </style>
</head>
<body>
<div class="limiter">
    <div class="container-login100" style="background:#4CAACA;">
        <div class="wrap-login100 wrapps" style="padding: 117px 130px 100px 95px;">
        <div class="login100-pic js-tilt"  style="margin-top: 10% !important;">
					<img class="main-logo" src="{{ asset('img/logo/logo-recortado.png') }}"  />
				</div>

                    <form class="login100-form validate-form" method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <span class="login100-form-title">Cambiar Contraseña</span>
                        <label  for="exampleInputEmail1" class="poppins-regular " style="margin-left:10px; margin-top:-35px; padding-bottom:10px;">Ingrese su nueva contraseña y confirme para completar el proceso de cambio de contraseña.</label>

                        <!-- email -->
                        <div class="wrap-input100 validate-input">
                           
                                <input class="input100" id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
                                <span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-envelope" aria-hidden="true"></i>
						</span>
                               
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            
                        </div>
                        <!-- nueva contraseña -->
                        <div class="wrap-input100 validate-input" >
                           
                                <input class="input100" id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" placeholder="Nueva Contraseña" required autocomplete="new-password">
                                <span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                         
                        </div>
                                <!-- Confirmar contraseña -->
                        <div class="wrap-input100 validate-input" >

                         
                                <input class="input100" id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="Confirmar Contraseña" required autocomplete="new-password">
                                <span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
                        </div>

                        <div class="container-login100-form-btn">
                            
                                <button class="login100-form-btn" type="submit" class="btn btn-primary" style="background-color:#3077b7 !important;">
                                    Guardar
                                </button>
                                <div class="text-center p-t-12">
						<a class="txt2" href="{{ url('/') }}">
							Regresar al inicio
						</a>
					</div>
                           
                        </div>
                    </form>
                
          
        </div>
    </div>
</div>
</body>
</html>
