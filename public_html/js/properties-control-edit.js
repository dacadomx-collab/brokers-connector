var form_prop; //Varible de control para validar el formulario teniendo en cuenta los tabs
var classTabActive="tab1"; //Varible para controlar la el tab activo

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



//funcion para validar la informacion del tab 1
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

//Validar al hacer el click en el tab 1
$(".prop").click(function(){
    classTabActive="tab1";
})

$(".addicional").click(function(){
    classTabActive="tab2";
    validFormTab1();
});

$(".ftrs").click(function(e){
    classTabActive="tab3";
});


//Validar al hacer click en guardar
$("#save-property").click(function(){
    if(classTabActive=="tab1")
    {
        validFormTab1();
    }

    if (!form_prop)
    {
        add_band=false;
        setTimeout(() => {
            alert('Por favor complete los campos obligatorios para continuar');
        }, 300);
        return ;
    }

    //Validar el campo de año si es vacio
    if($("#op2").is(":checked"))
    {
        if($("#year").val()=="")
        {
            setTimeout(() => {
                alert('Por favor complete los campos obligatorios para continuar');
            }, 300);
            $("#error-year").removeClass("hidden");
            return ;
        }
    }

    //Ocultar el mensaje si no esta oculto
    if(!$("#error-year").hasClass("hidden"))
    {
        $("#error-year").addClass("hidden");
    }

    $("#form").submit();
       

});