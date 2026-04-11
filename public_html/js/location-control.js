
//Variables globales
var language={
    noResults: function() {
        return "No hay resultado";        
    },
    searching: function() {
        return "Buscando..";
    }
}

var state ='';
var city = '';
var loc ='';

//Estados
// $('#state').select2({
//     placeholder:"Elegir un estado",
//     language: language
// });




//ciudades
$('#cities').select2({
    ajax: {
        delay: 500,
        url: '/get-cities',
        data: function (params) {
            var queryParameters = {
            q: params.term,
                state: $('#state').val()
           }

            return queryParameters;
    },
       processResults: function (data) {
           
            return data;
            
        }
    },
    placeholder:"Elegir ciudad / municipio",
   language: language
    
});

//Funcion que se ejecuta cuando se carga la página
window.onload=function() {
    $('#state').change(function(){
        $("#loc").select2("val", null);
        $("#cities").select2("val", null);
        state = $('option:selected',this).text();
        //console.log(state+city+loc)
    })
    
    $('#cities').change(function(){
        
        $("#loc").select2("val", null);
        city = $('option:selected',this).text();
        //console.log(state+city+loc)
    })

    var count=0; //Prevenir buscar en el mapa 2 veces
    $('#loc').change(function(){

        if(count)
        {
            loc = $('option:selected',this).text();
            console.log(loc);
            
            if (loc) {
                getCoordinates()
            }

        }

        count++;
    })
}


//colonias
$('#loc').select2({
    ajax: {
        delay: 500,
        url: '/get-districts',
        data: function (params) {
            var queryParameters = {
                q: params.term,
                city: $('#cities').val()
            }

            return queryParameters;
        },
        processResults: function (data) {
           
            return data;
        }
    },
    placeholder:"Elegir colonia",
    language: language
    
});


function getCoordinates(){

    
    let querystring = '';
    var state_text=$('option:selected',"#state").text();
    var city_text=$('option:selected',"#cities").text();
    var loc_text=$('option:selected',"#loc").text();

    if(state_text && city_text && loc_text){
        querystring = querystring + state_text +'+'+ city_text +'+'+ loc_text;
    }
 
    if(querystring!="")
    {
        $.ajax({
            url: 'https://maps.googleapis.com/maps/api/geocode/json?address='+querystring+'&key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84',
            type: 'GET',   
            success: function (res) {
                
              
    
               var RES_LAT=res.results[0].geometry.location.lat;
               var RES_LNG=res.results[0].geometry.location.lng;
           
               $("#lat").val(RES_LAT);
               $("#lng").val(RES_LNG);
    
               if (marker && marker.setMap) 
               {
                   marker.setMap(null);
               }
               
               marker = new google.maps.Marker({
                map: map,
                animation: google.maps.Animation.DROP,
                position: {lat: RES_LAT, lng: RES_LNG}
            });
    
               map.setCenter(marker.getPosition());
                
             
             
            },      
            error: function (res) {
               console.error('algo salio mal')
            },      
        });

    }

}

//apikey//AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84//24.151563, -110.311586