<?php $__env->startSection('title', $properties->total().' propiedades encontradas'); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('/')); ?>">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Mis propiedades</span>
</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<link rel="stylesheet" href="<?php echo e(asset('admin/css/select2/select2.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">


<style>
    .heart-on {
        cursor: pointer;
        color: #ff9800;

    }

    .heart-on:hover {
        
        color: #9e9e9e;

    }

  

    .heart-off {
        cursor: pointer;
        color: #999;

    }

    .heart-off:hover {
        color: black;

    }

    .heart {
        
    }


    .ref_underline{
        color: black;
    }
    .ref_underline:hover{

        color: black;
        text-decoration: underline;
    }

    .text-yellow{
        color: #ff9800;
    }


.star{
    position: absolute;
    top: 5px;
    right: 5px;
    cursor: pointer;
    float: right;
    color: #999;

        
}

.star:hover{
    color: gray;
}

.star-active{
  
    color: #ff9800;

}

.star-list{
    position: inherit;
    float: none;
}

  /*codigo para el menu de arriba*/
  .mfp-zoom-out-cur {
        cursor: pointer;
        }
        .mfp-zoom-out-cur .mfp-image-holder .mfp-close {
        cursor: pointer;
        }
        .container-image {
            position: relative;
            width: 100%;
            height: 250px;
            /*box-shadow: 2px 9px 20px;*/
          /*  cursor: pointer;*/
           /* margin-bottom: 25px;*/
         
          }
          .select-image{
            background: #3077b7;
            padding: 10px;
          }
          
          .image {
              display: block;
              width: 100%;
              height: -webkit-fill-available;
              object-fit: contain;
            }
          
          .overlay {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100%;
            width: 100%;
            opacity: 1;
            transition: .3s ease;
            background-color:transparent;
            cursor: pointer;
          
          }
          .container-image:hover .overlay {
            opacity: 1;
          }
          
          .text {
            color: white!important;
            //font-size: medium;
            
            position: absolute;
            width:100%;
            top: 50%;
            left: 50%;
            -webkit-transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            text-align: center;
          }
          .options{
          
            position: fixed;
            z-index: 100;
            width:100%;
            background: #3077b7;
            color: white;
            top:0;
            margin-top:0px;
            display:none;
            padding: 13px 20px;
          }
          .btn-transparent{
            color: #ffffff;
            background-color: #fff0;
            border-color: #ffffff;
            min-width: 150px;
            margin: 0 10px;
          }
          .btn-transparent:hover{
            color: #000;
            background-color: white;
            border-color:#3077b7;
           
          }
          .btn-transparent:hover i.fa-star{
           color: #ff9800 !important;
           
          }
          .btn-transparent:hover i.fa-eye{
           color: #3077b7 !important;
           
          }
          .btn-transparent:hover i.fa-times{
           color: #f44336 !important;
           
          }
          .featured{
           position: absolute;
           z-index: 10;
           margin: 2px 22px;
           right:0;
           color: #ffeb3b;
           font-size: x-large;
          }

          /* <?php if(Session::has('view')): ?>
            <?php if(!Session::get('view')): ?>
            .icheckbox_square-green{
                position: absolute;
                top: 5px;
                left: 5px;
                }
            <?php endif; ?>
          
          <?php endif; ?>
     */
         

          
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div id="options" class="options" style="background-color:#4caf50; z-index: 99999;">
    <div class="row">
        <div class="col-xs-11">
            
            <button type="button" id="share-button" class="btn btn-delete-file btn-transparent"><i class="fa fa-share"></i> 
                <span id="text-send-email"> Enviar por email </span></button>
            <button type="button" id="share-button" class="btn btn-cancel btn-transparent"><i class="fa fa-ban" aria-hidden="true"></i> Cancelar</button>
            
            </span>

        </div>
    </div>
</div>

<div class="breadcome-area form-inline">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div
                    style="padding-top: 20px;background: #fff;padding-left: 20px;padding-right: 20px;margin-bottom: 15px;padding-bottom: 20px;">
                    <form  autocomplete="off" action="<?php echo e(route('search.properties')); ?>" method="get">
                        <div class="row">
                            <div class="col-md-10">
                                <div class="row">
                                    <div class="col-md-2 col-sm-6">
                                        <label for="status_select">Operación</label>
                                        <select class="form-control" name="status" id="status_select"
                                            style="width:100%;">
                                            <option value="">Elegir</option>
                                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option
                                                <?php if(isset($old_inputs, $old_inputs['status'])): ?><?php echo e($old_inputs['status']==$status->id ? "selected" : ""); ?><?php endif; ?>
                                                value="<?php echo e($status->id); ?>"><?php echo e(ucfirst($status->name)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label for="type_select">Propiedad</label>
                                        
                                        <select data-placeholder="Escoge uno o varios" class="chosen-select form-control" name="type[]" multiple="" tabindex="-1" style="width:100%;">
                                            <option value="">Select</option>
                                            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option
                                                <?php if(isset($old_inputs, $old_inputs['type'])): ?> <?php if(in_array($type->id, $old_inputs['type'])): ?> selected <?php endif; ?> <?php endif; ?>
                                                value="<?php echo e($type->id); ?>"><?php echo e(ucfirst($type->name)); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </div>

                                    <div class="col-md-2 col-sm-6">
                                        <label for="price">Precio min.</label><br>
                                        <input type="text" class="form-control price" placeholder="Min" name="price_min"
                                            id="price" max="9999999999" style="width:100%;"
                                            value="<?php if(isset($old_inputs, $old_inputs['price_min'])): ?><?php echo e($old_inputs['price_min']); ?><?php endif; ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label for="price">Precio max.</label><br>
                                        <input type="text" class="form-control price" placeholder="Max" name="price_max"
                                            max="9999999999" style="width:100%;"
                                            value="<?php if(isset($old_inputs, $old_inputs['price_max'])): ?><?php echo e($old_inputs['price_max']); ?><?php endif; ?>">
                                    </div>

                                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                        <label for="state">Estado</label>
                                        <select  class="form-control" name="state"  id="states" style="width:100%;"></select> 
                                    </div>

                                    <div class="col-md-2">
                                        <label for="city">Ciudad</label>
                                        
                                            <select  class="form-control" name="city"  id="cities" style="width:100%;"></select> 
                                    </div>
                                    
                                </div>

                                <div class="row " style="margin-top:25px;">
                                    <div class="col-lg-2">
                                        <button id="button-advanced" type="button" class="btn btn-default"
                                            style=" width:-webkit-fill-available">Más filtros</button>
                                    </div>

                                    <div class="col-lg-10">
                                        <?php if(Session::has('view')): ?>

                                        <a id="view" style="min-height:40px;"
                                            href="/properties/change-view?value=<?php echo e(Session::get('view') ? '0' : '1'); ?>"
                                            data-toggle="tooltip" title="Cambiar vista" class="btn btn-custon-four">
                                            <i style="vertical-align: middle;"
                                                class="fa <?php echo e(Session::get('view') ? 'fa-th-large' : 'fa-list'); ?>"></i></a>
                                        <?php else: ?>
                                        <a id="view" style="min-height:40px;" href="<?php echo e(url('properties/change-view')); ?>?value=1"
                                            data-toggle="tooltip" title="Cambiar vista" class="btn btn-custon-four">
                                            <i style="vertical-align: middle;" class="fa fa-list"></i></a>
                                        <?php endif; ?>



                                        <select class="form-control" name="order">
                                            <option value="">Ordenar por : </option>
                                            
                                            <option <?php if(isset($old_inputs, $old_inputs['order'])): ?><?php echo e($old_inputs['order']==1 ? "selected" : ""); ?><?php endif; ?> value="1">Precio mas bajo</option>
                                            <option <?php if(isset($old_inputs, $old_inputs['order'])): ?><?php echo e($old_inputs['order']==2 ? "selected" : ""); ?><?php endif; ?> value="2">Precio mas alto</option>
                                            <option <?php if(isset($old_inputs, $old_inputs['order'])): ?><?php echo e($old_inputs['order']==3 ? "selected" : ""); ?><?php endif; ?> value="3">Titulo ascendente (A-Z)</option>
                                            <option <?php if(isset($old_inputs, $old_inputs['order'])): ?><?php echo e($old_inputs['order']==4 ? "selected" : ""); ?><?php endif; ?> value="4">Titulo descendente (Z-A)</option>
                                            <option <?php if(isset($old_inputs, $old_inputs['order'])): ?><?php echo e($old_inputs['order']==5 ? "selected" : ""); ?><?php endif; ?> value="5">Marcados como favorito</option>
                                            
                                        </select>
                                    </div>
                                   
                                </div>

                                <div class="row" id="advanced"
                                    style="display:<?php if(isset($old_inputs)): ?><?php echo e($btn_more_display ? 'block' : 'none'); ?><?php else: ?> none <?php endif; ?>; margin-top: 15px;">

                                    <div class="col-md-4">
                                        <label for="search">Buscar por nombre</label>
                                        <input type="text" class="form-control" placeholder="Ingresa el nombre"
                                            name="search" id="search" style="width:100%;"
                                            value="<?php if(isset($old_inputs, $old_inputs['search'])): ?><?php echo e($old_inputs['search']); ?><?php endif; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="parking">Estacionamientos</label>
                                        <input type="number" class="form-control" placeholder="Cantidad" min="0"
                                            name="parking" id="parking" style="width:100%;"
                                            value="<?php if(isset($old_inputs, $old_inputs['parking'])): ?><?php echo e($old_inputs['parking']); ?><?php endif; ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="baths">Baños</label>
                                        <input type="number" class="form-control" name="baths" placeholder="Cantidad"
                                            min="0" id="baths" style="width:100%;"
                                            value="<?php if(isset($old_inputs, $old_inputs['baths'])): ?><?php echo e($old_inputs['baths']); ?><?php endif; ?>">
                                    </div>

                                    <div class="col-md-2">
                                        <label for="room">Habitaciones</label>
                                        <input type="number" class="form-control" name="rooms" placeholder="Cantidad"
                                            min="0" id="room" style="width:100%;"
                                            value="<?php if(isset($old_inputs, $old_inputs['rooms'])): ?><?php echo e($old_inputs['rooms']); ?><?php endif; ?>">
                                    </div>
                                </div>

                                <div class="row" style="margin-top:10px;">
                                    


                                </div>

                            </div>
                            <div class="col-md-2">
                                
                                <button class="btn btn-primary" style="width:-webkit-fill-available; margin-top: 25px;">
                                    <i class="fa fa-search" aria-hidden="true"></i>
                                </button>
                                <?php if(isset($old_inputs)): ?>
                                <a class="text-center" href="<?php echo e(route('show.all.properties')); ?>">Limpiar campos</a>
                                <?php endif; ?>

                                
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="courses-area">
    <div class="container-fluid" style="display:block">
        <?php if($properties->count() == 0): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="" align="center">
                    <h3></h3>
                    <div class=" h-100 d-flex justify-content-center align-items-center">
                        <div class="container theme-showcase" role="main">
                            <?php if(isset($old_inputs)): ?>
                            <div class="jumbob">
                                <h2 style="">No se encontraron propiedades<i class="fa fa-sad-tear"></i></h2>
                            </div>
                            <?php else: ?>
                            <div class="jumbob">
                                <h2 style="">Aún no tienes propiedades <i class="fa fa-sad-tear"></i></h2>
                                <p>Intenta agregar algunas</p>
                                <a href="<?php echo e(route('create.propertie')); ?>" class="btn btn-default">AGREGAR</a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div align="center">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="row">
            <form id="form-propertys" name="form-propertys" method="get"  autocomplete="off" action="<?php echo e(route('view.email')); ?>">
            <?php if(Session::has('view')): ?>
                <?php if(Session::get('view')): ?>
                <?php echo $__env->make('properties.utils.properties-list', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php else: ?>
                <?php echo $__env->make('properties.utils.properties-grid', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                <?php endif; ?>
            <?php else: ?>
            <?php echo $__env->make('properties.utils.properties-grid', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <?php endif; ?>
        </form>
        </div>
    </div>
</div>


<div class="col text-center">
    <?php echo e($properties->appends(Request::except('page'))->links()); ?>

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
<script src="<?php echo e(asset('admin/js/icheck/icheck.min.js')); ?>"></script>
<script src="<?php echo e(asset('admin/js/icheck/icheck-active.js')); ?>"></script>
<script src="<?php echo e(asset('js/selec2.js')); ?>"></script>
<?php if(Session::has('success')): ?>
<script>
    Lobibox.notify('success', {
        title: 'Notificación',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "<?php echo e(session('success')); ?>"
    });



   
</script>
<?php endif; ?>

<script>

    <?php if(Session::has('fail')): ?>

    Lobibox.notify('error', {
        title: 'Notificación',
        position: 'top right',
        showClass: 'fadeInDown',
        hideClass: 'fadeUpDown',
        msg: "<?php echo e(session('fail')); ?>"
    });
    <?php endif; ?>

$('input').iCheck({
    checkboxClass: 'icheckbox_square-green',
		radioClass: 'iradio_square-green',
});

// $(window).bind("pageshow", function() {
//     $("#seleccionar-todo").iCheck('uncheck');
//     $(".checkbox_property").iCheck('uncheck');
//     $("#form-propertys").trigger("reset");
//     $(".checkbox_property").prop('checked',false);
//     $('#options-single').fadeOut();
  
// });

    $(document).ready(function(){

        //Inicializar el selector multiple de tipos
        $('.chosen-select').select2({closeOnSelect:false});

        //Inicializar el selector de ciudades
        $('#cities').select2({
            ajax: {
                delay: 500,
                url: '<?php echo e(route("property.city.filter")); ?>',
                data: function (params) {
                   
                    var queryParameters = {
                    q: params.term,
                    state:$("#states").val()
                        
                }

                    return queryParameters;
            },
            processResults: function (data) {
                
                    return data;
                    
                }
            },
            placeholder:"Elegir ciudad / municipio",
            language: {
                noResults: function() {
                    return "No hay resultado";        
                },
                searching: function() {
                    return "Buscando..";
                }
            }
                
        });

          //Inicializar el selector de estados
          $('#states').select2({
            ajax: {
                delay: 500,
                url: '<?php echo e(route("property.state.filter")); ?>',
                data: function (params) {
                   
                    var queryParameters = {
                    q: params.term,
                    
                        
                }

                    return queryParameters;
            },
            processResults: function (data) {
                
                    return data;
                    
                }
            },
            placeholder:"Elegir estado",
            language: {
                noResults: function() {
                    return "No hay resultado";        
                },
                searching: function() {
                    return "Buscando..";
                }
            }
                
        });

        //Metodo para dejar datos de ciuadaes al filtrar
        <?php if(isset($old_inputs, $old_inputs['city'])): ?>
            getTextById(<?php echo e($old_inputs['city']); ?>, "/get-mun-id", "#cities");
        <?php endif; ?>
        //Metodo para dejar datos de estados al filtrar
        <?php if(isset($old_inputs, $old_inputs['state'])): ?>
            getTextById(<?php echo e($old_inputs['state']); ?>, "/get-state-id", "#states");
        <?php endif; ?>
        
        //Funcion para obtener la ciudad elegida al filtrar
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

        <?php if(Session::has("email-properties")): ?>
        
        if(<?php echo e(count(Session::get('email-properties'))); ?> > 0)
        {
            $("#text-send-email").text("Regresar");
            $('#options').stop(true, true).fadeIn();

        }
        var array=[<?php $__currentLoopData = Session::get('email-properties'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>"<?php echo e($item); ?>",<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>];

        var array_properties=[<?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>"<?php echo e($property->id); ?>",<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>];
        array.forEach(element => {
            if(jQuery.inArray(element, array_properties) == -1)
            {
                var input1 = document.createElement('input');
                input1.setAttribute('type', 'checkbox');
                input1.setAttribute('checked', true);
                input1.setAttribute('name', 'checkbox_property[]');
                input1.setAttribute('value', element);
                input1.setAttribute('style', "display:none;");
              
                $("#form-propertys").append(input1);
            }
        });

        $(".checkbox_property" ).each(function( index ) {
            
            if(jQuery.inArray($(this).val(), array) != -1) 
            {
                $(this).iCheck('check');
                console.log($(this).val());
            } 
            else
            {
                $(this).iCheck('uncheck');
            }
            
        });
        
        function validarCampos() {
            var modified;
            

            if(modified)
            {
                return confirm('Puede haber cambios sin guardar en el formulario, ¿Desea salir de todas formas?'); 

            }
          
        }
            
            
        window.onbeforeunload = validarCampos;

        <?php endif; ?>
    });

    






            //Habiltar de nuevo el evento change del checkbox marcar todos
    $('#seleccionar-todo').on('ifChanged', function(event, from){
       
       if (event.currentTarget.checked) //Revisar el estado del checkbox de marcar todos 
       {
           $(".checkbox_property").iCheck('check'); //Activar todos los checkbox visibles
       }
       else
       {
           $(".checkbox_property").iCheck('uncheck'); //Desactivar todos los checkbox visibles
       }

   });


var xhr;

    $(".price").keypress(function (e) {
        if (e.which != 8 && e.which != 0 && e.which != 46 && (e.which < 48 || e.which > 57)) {
            return false;
        } else {
            if (e.which == 44) {
                return false;
            } else if (e.which == 46) {
                if (this.value.split('.').length >= 2) {
                    return false;
                }
            }
        }
    });

    $('.button-edit').click(function(){
       let url = $(this).data('link');
       window.location.href = url;
    })

    $('#button-advanced').click(function () {
        $('#advanced').fadeToggle();
    })

    $(".delete-property").click(function () {
        var id = $(this).data("property");
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: true,
            msg: "¿Desea eliminar esta propiedad?",
            buttons: {
                yes: {
                    class: 'btn btn-primary waves-effect waves-light',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                },
            },
            callback: function ($nop, type, ev) {
                if (type == "yes") {
                    $.ajax({
                        url: '/properties/delete',
                        data: {
                            id: id
                        },
                        type: 'POST',
                        success: function (res) {

                            $("#row-property" + id).fadeOut(function () {
                                $(this).remove();
                            });

                            Lobibox.notify('success', {
                                title: 'Propiedad eliminada',
                                position: 'top right',
                                showClass: 'fadeInDown',
                                hideClass: 'fadeUpDown',
                                msg: res
                            });
                        },
                    });
                }
            }
        });
    });



    $('.status-btn').click(function (e) {
        const span = $(this);
        var button = span.children('button');
        var property_id = span.data('id');
        var published = span.data('published');

        Lobibox.confirm({
            title: 'Confirmar',
            iconClass: true,
            msg: "¿Cambiar estado de la propiedad?",
            buttons: {
                yes: {
                    class: 'btn btn-primary waves-effect waves-light',
                    text: 'Confirmar',
                    closeOnClick: true
                },
                no: {
                    class: 'btn btn-default',
                    text: 'Cancelar',
                    closeOnClick: true
                },
        },
            callback: function ($nop, type, ev) {
                if (type == "yes") {

                    $.ajax(
                        {
                            type: "post",
                            url: "/properties/state",
                            data: { published: published, property_id: property_id, "_token": "<?php echo e(csrf_token()); ?>" },
                            success: function (result) {

                                //alert("estado actual " + result.published);
                                var estatus = "";
                                span.data('published', result.published)
                                console.log(span.data('published'));
                                if (result.published == 1) {
                                
                                   button.children('i').removeClass('fa-times text-danger').addClass('fa-check text-success')
                                   
                                    estatus = "<i class='fa fa-check' ></i> El estado de la propiedad cambio a publicado";
                                } else {
                                    
                                    button.children('i').removeClass('fa-check text-success').addClass('fa-times text-danger')

                                    estatus = "<i class='fa fa-times' ></i> El estado de la propiedad cambio a no publicado";
                                }
                                Lobibox.notify('success', {
                                    title: 'Exito',
                                    position: 'top right',
                                    showClass: 'fadeInDown',
                                    hideClass: 'fadeUpDown',
                                    msg: estatus
                                });
                              

                            },
                            error: function (result) {
                                alert("error");
                            }
                        });
                }
            }
        });
    });

</script>
<script>



$('.star').click(function(){
   if (xhr != null) {
    xhr.abort();
   }
    let star = $(this);
    let id_property = star.data('id');
    
     $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
    xhr =  $.ajax({
            url: "/properties/changeStatus",
            method: "POST",
            data: {
                id_property: id_property
            },
            success( res){
               if (res.featured) {
                   
                   star.addClass('star-active').children('i').removeClass('fa-star-o').addClass('fa-star bounceIn animated');
                } else {
                    star.removeClass('star-active').children('i').removeClass('fa-star bounceIn animated').addClass('fa-star-o'); 
               }
            }
        });
})



</script>
<script >
    
    const optionsBar = $('#options');
    const images = $('.img');

    //boton de cancelar
    $(".btn-cancel").click(function(){
        <?php if(Session::has("email-properties")): ?>
            window.location="/properties/email?<?php $__currentLoopData = Session::get('email-properties'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>checkbox_property[<?php echo e($loop->iteration-1); ?>]=<?php echo e($item); ?>&<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>"
        <?php else: ?>
            window.location="<?php echo e(url('properties/email')); ?>";
        <?php endif; ?>
      
    })

    // Verficar si hay propieadades en la sesion
   $('.checkbox_property').on('ifChanged', function(event, from){
    
            $("#text-send-email").text("Enviar por email");
            $(this).toggleClass('select-image')
            
            if($('.select-image').length){
                optionsBar.stop(true, true).fadeIn();
                if ($('.select-image').length > 1) {
                    $('#options-single').fadeOut();
                }else{
                    $('#options-single').stop(true, true).fadeIn();
                }
            }else{
                <?php if(Session::has("email-properties")): ?>
                    $("#text-send-email").text("Regresar");
                <?php else: ?>
                    optionsBar.fadeOut();
                <?php endif; ?>
               
            }
  
    
    //alert("aaaa");
   


  });
  $('.btn-open').click(function(){
    
    $('.select-image').first().children('div').children('a').trigger('click');
  });
    <?php if($errors->any()): ?> 
        <?php $__currentLoopData = $errors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        Lobibox.notify('error', {
                    title: 'Error',
                    position: 'top right',
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: "<?php echo e($error); ?>"
                });
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>









    //Compartir la propiedad
        
    $('#share-button').click(function(){
    const form = document.getElementById('form-propertys');
    console.log(form);
    form.submit();
    /*
    var array_propertys = [];
    $("input:checkbox[name=checkbox_property]:checked").each(function(){
        array_propertys.push($(this).val());
    });
    var formData = new FormData();
    formData.append("checkbox_property[]", array_propertys);
    formData.submit();
    */
/*
    const form = document.createElement('form');
    form.method = 'post';
    form.action = '<?php echo e(route('view.email')); ?>';
    $("input:checkbox[name=checkbox_property]:checked").each(function(){
        array_propertys.push($(this).val());
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = 'array_propertys[]';
        hiddenField.value = $(this).val();
        form.appendChild(hiddenField);
        
    });
    const a = document.createElement('input');
        a.type = 'hidden';
        a.name = '_token';
        a.value = $('meta[name="csrf-token"]').attr('content');
        //alert($('meta[name="csrf-token"]').attr('content'))
        form.appendChild(a);
    document.body.appendChild(form);
    form.submit();
*/
 /*
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    var request = $.ajax({
        async:false,
        url: '<?php echo e(route('view.email')); ?>',
        method: "POST",
        data: { array_propertys : array_propertys },
        dataType: "html"
    });
        
    request.done(function( msg ) {
      alert(msg);
    });
        
    request.fail(function( jqXHR, textStatus ) {
        alert( "Request failed: " + textStatus );
    });
*/
    
});
</script>

<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers\resources\views/properties/index.blade.php ENDPATH**/ ?>