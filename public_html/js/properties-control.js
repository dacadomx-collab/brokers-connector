var classTrigger=".prop"; //Variable de control para determinar al tab que se dio click

var add_band=false;

//Funcion para mostrar los datos del select despues de una validacion del lado del servidor
async function getTextById(id, url, select_id)
{
  const berni =  await $.ajax({
        url: url,
        data: {id:id},                
        type: 'get',   
        success: function (res) {
            $(select_id).empty().append('<option value="'+res.id+'">'+res.text+'</option>').val(res.id).trigger('change');
        },      
    });
}

//Funcion para revisar el formulario valido del tab propiead
function validFormTab1(){
    state_valid=true;
    mun_valid=true;
    loc_valid=true;

    if($("#state").val()=="")
    {
        $("#error-state").removeClass("hidden")
        state_valid=false;
    }
    else
    {
        $("#error-state").addClass("hidden")
    }

    if($("#cities").val()==null)
    {
        $("#error-mun").removeClass("hidden")
        mun_valid=false;

    }
    else
    {
        $("#error-mun").addClass("hidden")
    }

    if($("#loc").val()==null)
    {
        $("#error-loc").removeClass("hidden")
        loc_valid=false;
       
    }
    else
    {
        $("#error-loc").addClass("hidden")
    }

    if (!$("#form").valid() || !state_valid || !mun_valid || !loc_valid)
    {
        form_prop=false;
    }
    else
    {
        form_prop=true;
    }
}



$(document).ready(function(){
    $('.selectpicker').selectpicker();

    //Esconder o mostrar el input del año de ingreso
    $("#op2").on("ifChanged", function() {
        
        //Ocultar el mensaje si no esta oculto
        if(!$("#error-year").hasClass("hidden"))
        {
            $("#error-year").addClass("hidden");
        }

        if($(this).prop("checked"))
        {
            $("#year").removeClass("hidden");
            $("#year").prop("required", true);
        }
        else
        {
            $("#year").addClass("hidden");
            $("#year").prop("required", false);
        }
    });

    //Validar el año que se ingresa con numeros 
    $("#year").keypress(function (e) {
    
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
   
           return false;
        }
    });

})

const form = $("#form");
form.validate();

//Desactivar el tab de caracteristicas al no tener validado el formulario
$(".ftrs").click(function(e){
    
    if (!form_prop || !add_band)
    {
        $(this).removeClass('active')

        $(classTrigger).addClass('active')
    }
    
    
});

$(".prop").click(function(){
    classTrigger=".prop";
})

$(".addicional").click(function(){
  
    validFormTab1();

    classTrigger=".addicional";
})


$('#next').click(function () {
   
   
    if($("#commission").val()>0)
    {
        $("#type_commission").prop('required',true);
    }
    else
    {
        $("#type_commission").removeAttr('required');
    }

    
    validFormTab1();
    

    if(!form_prop)
    {
        return ;
    }
    
    $("#inf_adv").trigger("click");
    // $(".prop").addClass('active');
    
    })

    $("#next-step-2").click( function() {
    
    //Validar el campo de año si es vacio
    if($("#op2").is(":checked"))
    {
        
        if($("#year").val()=="")
        {
            $("#error-year").removeClass("hidden");
            return ;
        }
    }

    //Ocultar el mensaje si no esta oculto
    if(!$("#error-year").hasClass("hidden"))
    {
        $("#error-year").addClass("hidden");
    }

    if (!form_prop)
    {
        add_band=false;
        $("#prop").css("color","red");
        setTimeout(() => {
            alert('Por favor complete los campos obligatorios para continuar');
        }, 300);
        return ;
    }

    
    add_band=true;

    $("#prop").css("color","");

    $("#features").attr("href","#reviews");
    $("#features").trigger("click");
    
    $(".addicional").removeClass('active');
    
    $(".ftrs").addClass('active');
    $(".ftrs a").removeClass('isDisabled');
    $(".ftrs").removeClass('disabled');
    
});

$("#nextimages").click(function(){
    if (!form_prop)
    {
        $("#prop").css("color","red");
        return ;
    }

   

    $("#form").submit();
});