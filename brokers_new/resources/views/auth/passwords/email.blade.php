<!DOCTYPE html>
<html lang="en">
<head>
	<title>Recuperar Contraseña</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
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
</style>
</head>
<body>
	<div class="limiter">
		<div class="container-login100" style="background:#4CAACA;"> 
			<div class="wrap-login100 wrapps" style="padding: 117px 130px 100px 95px;">
                   
            <div class="login100-pic js-tilt"  style="margin-top: 10% !important;">
					<img class="main-logo" src="{{ asset('img/logo/logo-recortado.png') }}"  />
				</div>

				<form class="login100-form validate-form"  method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="col-md-12 row" >
                    @if (session('status'))
                    <div class="alert alert-success" role="alert" >
                    Revisa tu correo, por favor. Si no está en la bandeja de entrada, verifica la carpeta de spam.</div>
                         @endif
                    </div>
                    
                    <span class="login100-form-title">
						Recuperar Contraseña
					</span>
                    <label  for="exampleInputEmail1" class="poppins-regular " style="margin-left:10px; margin-top:-35px; padding-bottom:10px;">Ingrese su dirección de correo electrónico y le enviaremos un enlace para restablecer su contraseña.</label>
                    <div class="wrap-input100 validate-input" data-validate = "Valid email is required: ex@abc.xyz">
						<input class="input100"  type="email" name="email" placeholder="Correo electroníco" required>
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-envelope" aria-hidden="true"></i>
						</span>
					</div>
                    <div class="container-login100-form-btn">
						<button class="login100-form-btn" style="background-color:#3077b7 !important;">
                            Enviar
                        </button>
					</div>
                    <div class="text-center p-t-12">
						<a class="txt2" href="{{ url('/') }}">
							Regresar al inicio
						</a>
					</div>
                  
					
					
					
					

					
				</form>
			</div>
		</div>
	</div>
	
	


</body>
</html>