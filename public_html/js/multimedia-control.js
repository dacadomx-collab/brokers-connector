
const property_id = $('#property_id').val();

function deleteFile(id)
{
     if(id!=null)
     {
         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
                url: '/files/upload/delete',
                data: {id:id},                
                type: 'POST',   
                success: function (res) {
                    
                    Lobibox.notify('success', {
                        title: 'Archivo eliminado',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: res.msg
                    });
                    $("#div-file-"+id).fadeOut();
                    $("#div-file-"+id).removeClass('video');
                    videos_count();
                    
                 
                },      
                error: function (res) {
                    Lobibox.notify('error', {
                        title: 'Algo salio mal',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: 'Ocurrio un error mientras se eliminaba el archivo, por favor contacte a soporte'
                    });
                    $("#div-file-"+id).fadeOut();
                },      
         });
     }
}

function deleteFileFromArray(array, elements)
{
     if(array.length)
     {
        
         $.ajaxSetup({
             headers: {
                 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
             }
         });

         $.ajax({
                url: '/files/upload/delete-files',
                data: {
                    images: array,
                    property_id : property_id
                },                
                type: 'POST',   
                success: function (res) {
                    Lobibox.notify('success', {
                        title: 'Imágenes eliminadas',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: res.msg
                    });

                   elements.each(function() {
                      let el = $(this)
                       el.fadeOut(function(){
                       el.parent('div').remove();
                       images_count();
                   })
                  });
                    
              
                  
                     
                  

                },      
                error: function (res) {
                    Lobibox.notify('error', {
                        title: 'Algo salio mal',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: 'Ocurrio un error mientras se eliminaba el archivo, por favor contacte a soporte'
                    });
                    //$("#div-file-"+id).fadeOut();
                },      
         });
     }
}

$(document).ready(function(){
    

    // $('#div-images').lightGallery({
    //     selector: '.container-image'
    // });

    $("#div-yt").on("click",".btn-delete-yt", function(){
        var url=$(this).val();
        var id=$(this).data("id");
        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: false,
            msg: "Seguro desea eliminar a este archivo?",
            buttons: {
                yes: {
                    class: 'btn btn-primary',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                }
            },
            callback: function ($nop, type, ev) 
            {
               if(type=="yes")
               {
                    deleteFile(id);
                    

               }
            }
        });
        
    });

})

$("#div-images").magnificPopup({
     delegate: 'a', // child items selector, by clicking on it popup will open
     type: 'image',
     cursor: 'mfp-zoom-out-cur',
     gallery:{enabled:true}
     // other options
});

$(".div-img").hover(

     function() {
         var btn = $(this).find(".btn");
         btn.removeClass("hidden");
         
     }, function() {
         var btn = $(this).find(".btn");
         btn.addClass("hidden");
     }
);

$(".btn-massive-delete").click(function(){
    $(".div-confirmed").removeClass("hidden");
    $(".check-image-delete").removeClass("hidden");
});

$("#btn-cancel-delete").click(function(){
    $(".div-confirmed").addClass("hidden");
    $(".check-image-delete").addClass("hidden");
});

$("#btn-confirm-delete").click(function(){
    console.log($(".images_delete"));
    
});

// $(".btn-delete-file").click(function(){
//     var url=$(this).val();
//     var id=$(this).data("id");
//     Lobibox.confirm({
//         title: '¿Esta seguro?',
//         iconClass: false,
       
//         msg: "¿Estás seguro que desea eliminar los archivos seleccionados?",
//         buttons: {
//             yes: {
//                 class: 'btn btn-primary waves-effect waves-light',
//                 text: 'Confirmar',
//                 closeOnClick: true
//             },
//             no: {
//                 class: 'btn btn-default',
//                 text: 'Cancelar',
//                 closeOnClick: true
//             }
//         },
//         callback: function ($nop, type, ev) 
//         {
//            if(type=="yes")
//            {
//                 deleteFile(id);

//                 $("#div-file-"+id).removeAttr("style");
//                 $("#div-file-"+id).html("");
//            }
//         }
//     });
    
// })

$(".btn-delete-file").click(function(){
    var url=$(this).val();
   
   let files = $('.select-image').map(function(  ) {
    let id = $(this).data('id');
       return id;
        
    });

    let els = $('.select-image');
    deleteFileFromArray(files.toArray(), els);
    optionsBar.fadeOut();
    
})



 $(".dropzone").dropzone({ 
     acceptedFiles: "image/*, .mp4, .avi, .3gp, .jfif",
   
     addRemoveLinks: true,
     dictCancelUpload: "Cancelar subida",
     dictRemoveFile: "Eliminar archivo",
     success: function(file, responseText) {
         file['id']=null;
         
         switch (responseText.type_msg) {
             case 1:
                 Lobibox.notify('error', {
                 title: 'Archivo no subido',
                 position: 'top right',
                 showClass: 'fadeInDown',
                 hideClass: 'fadeUpDown',
                 msg: responseText.msg
                 });
                 break;
             case 2:
                 file['id']=responseText.id;
                 break;
         
             default:
                 break;
         }
              
     },
     
     error: function(file,res){
         
         $(file.previewElement).addClass("dz-error").find('.dz-error-message').text(res.msg);
         Lobibox.notify('error', {
             title: 'Archivo no subido',
             position: 'top right',
             showClass: 'fadeInDown',
             hideClass: 'fadeUpDown',
             msg: res.msg
         });
     },
     removedfile: function(file) {
         deleteFile(file.id);
         file.previewElement.remove();
     },

     queuecomplete: function(){
        //  debugger;
        setTimeout(() => {
            
             location.reload();
        }, 800);
     }
     
     
 });


 $(".input-youtube").change(function() {
     
    var input = $(this).val();  
    var valid = /^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/;
    
    if(!valid.test(input))
    {
        alert("Ingresa una url de Youtube valida.");
        $(this).val("");
        $(this).css('border-color','red');
        return false;
    }else
    {
        $(this).css('border-color','green');
	return true;
    }

 });

 


 $("#btn-save-yt").click(function(){
     var form = new FormData($('#form-yt')[0]);  
   

    $.ajax({
         url: '/files/upload/store',
         data: form,                
         cache: false,
         contentType: false,
         processData: false,
         type: 'POST',   
         success: function (res) {
             
             console.log(res, "---------RESPUESTA DE SAVE YOUTUBE videos ---------");
             
             Lobibox.notify('success', {
                 title: 'Archivo subido',
                 position: 'top right',
                 showClass: 'fadeInDown',
                 hideClass: 'fadeUpDown',
                 msg: res.msg
             });

             res.url.forEach(element => {
                 $("#div-yt").append('<div class="col-md-4 video" id="div-file-'+element.id+'" style="margin-bottom:15px;">'+
                     '<button data-id="'+element.id+'" class="btn btn-danger btn-delete-yt btn-xs" style="position:absolute; z-index: 1;" value="'+element.src+'">'+
                     '<i class="fa fa-times" style="font-size: large;padding: 5px;" aria-hidden="true"></i></button>'+
                     '<iframe src="'+element.src+'" frameborder="0" style="width: -webkit-fill-available; height: 150px;" ></iframe>'+
                 '</div>');
             });

             $('#enlace1').val('');
             videos_count();
            
         },
         error: function(res,textStatus){
         
             Lobibox.notify('error', {
                 title: 'Error',
                 position: 'top right',
                 showClass: 'fadeInDown',
                 hideClass: 'fadeUpDown',
                 msg: res.responseJSON.msg
             });

                             
         }      
     });  
  
 })

 $(".btn-featured").click(function(){

    const image = $('.select-image').first();
    const id = image.data('id');
        

     $.ajaxSetup({
         headers: {
             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         }
     });
     
     $.ajax({
         url: '/files/upload/set_featured',
         data: {id:id},
         type: 'POST',
         success: function (res) {
            Lobibox.notify('success', {
                 title: 'Imagen Destacada',
                 position: 'top right',
                 showClass: 'fadeInDown',
                 hideClass: 'fadeUpDown',
                 msg: res.msg
            });
            $(".featured").hide();
             image.parent('div').children('.featured').show();
             optionsBar.fadeOut();
             $('.select-image').removeClass('select-image');


            
             
            
            // element.removeClass("btn-primary");
            // element.addClass("btn-default");
        //
            // image.removeClass("btn-default");
            // image.addClass("btn-primary");
         },      
     });
 })

 function videos_count()
 {
   var number_videos = $(".video").length;
   if(number_videos == 1)
   {
       //si no tiene nada retorna falso.
       $("#no-videos").css('display', 'block');
       //alert("se muestra " + number_videos);
   }
   else{
        $("#no-videos").css('display', 'none');
        //alert("no se muestra " + number_videos);
   }
 }

 function images_count()
 {
   var number_images = $(".images-div").length;
   if(number_images == 0)
   {
       //si no tiene nada retorna falso.
       $("#no-imagenes").css('display', 'block');
      // alert("se muestra" + number_images);
   }
   else{
        $("#no-imagenes").css('display', 'none');
       // alert("no se muestra" + number_images);
   }
 }