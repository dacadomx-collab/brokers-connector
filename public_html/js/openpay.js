$(document).ready(function() {

    OpenPay.setId('mcwlrwxfbaj7vhzzkdou');
    OpenPay.setApiKey('pk_6a09a1bc164a4f9e9525287d5c919376');
    OpenPay.setSandboxMode(false);
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