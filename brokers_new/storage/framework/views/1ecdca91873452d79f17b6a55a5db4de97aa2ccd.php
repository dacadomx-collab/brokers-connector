<?php $__env->startSection('title','Agregar propiedad'); ?>

<?php $__env->startSection('breadcome'); ?>
<li><a href="<?php echo e(url('/')); ?>">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Agregar propiedad</span>
</li>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>

<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/Lobibox.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/notifications/notifications.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/touchspin/jquery.bootstrap-touchspin.min.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('admin/css/form/all-type-forms.css')); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css?version=<?php echo e(config('app.version')); ?>">
<style>
    .isDisabled {
        color: currentColor;
        cursor: not-allowed;
        opacity: 0.5;
        text-decoration: none;
    }

    hr{
        border-top: 1px solid #f5f5f5;

        }

        .select2-container--default .select2-selection--single{
            height: 40px !important;
            border-radius: 0px !important;
        }
    

</style>
<?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>

<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">

    
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="product-payment-inner-st">

            <ul id="myTabedu1" class="tab-review-design text-center">
                <li class="prop active"  ><a href="#description" id="prop">Propiedad</a></li>
                <li class="addicional"><a href="#advance-information" id="inf_adv">Adicional</a></li>
                <li class="ftrs disabled"><a role="button" class="features isDisabled" id="features" href="">
                        Características </a></li>
                
            </ul>

        </div>
        <form action="<?php echo e(url('properties/store')); ?>" onsubmit="window.onbeforeunload=null" id="form" method="POST" class="add-professors">

            <?php echo csrf_field(); ?>
            <input type="hidden" name="lat" id="lat" >
            <input type="hidden" name="lng" id="lng" >
          
            <div id="myTabContent" class="tab-content custom-product-edit ">

                <div class="product-tab-list tab-pane fade active in" id="description">
                    <?php echo $__env->make('properties.utils.tab-general' , ["data" => $property,"edit"=>false ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>

                
                <div class="product-tab-list tab-pane fade" id="reviews">
                    <?php echo $__env->make('properties.utils.tab-features' , ["data" => $property,"edit"=>false ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>

                
                <div class="product-tab-list tab-pane fade" id="advance-information">
                    <?php echo $__env->make('properties.utils.tab-addicional' , ["data" => $property,"edit"=>false ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>

            </div>
        </form>
    </div>
</div>


    <?php $__env->startPush('scripts'); ?>
    <!-- touchspin JS
        ============================================ -->
    <script src="<?php echo e(asset('admin/js/touchspin/jquery.bootstrap-touchspin.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/touchspin/touchspin-active.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/icheck/icheck.min.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/icheck/icheck-active.js')); ?>"></script>
    <script src="<?php echo e(asset('admin/js/notifications/Lobibox.js')); ?>"></script>
    <script src="<?php echo e(asset('js/jquery.validate.js')); ?>"></script>
    <script>
        var form_prop=false;
    </script>
    <script src="<?php echo e(asset('js/properties-control.js')); ?>"></script>

    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84&callback=initMap"
        async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script src="<?php echo e(asset('newbrokers/js/aiCopywriter.js')); ?>"></script>

    <script>
        $(document).ready(function() {
         
            //Dejar los datos de locacion despues de la validacion
            <?php if(old("state")): ?>
                $("#state").select2("val",<?php echo e(old("state")); ?>);
            <?php endif; ?>
            <?php if(old("mun")): ?>
                getTextById(<?php echo e(old("mun")); ?>, "/get-mun-id", "#cities");
            <?php endif; ?>
            <?php if(old("local_id")): ?>
                getTextById(<?php echo e(old("local_id")); ?>, "/get-loc-id", "#loc");
            <?php endif; ?>
            
            if(  typeof $("#loc").val() == 'undefined' || $("#loc").val() == null || $("#loc").val() == "" ){
                <?php if(old("local_id")): ?>
                getTextById(<?php echo e(old("local_id")); ?>, "/get-loc-id", "#loc");
                <?php endif; ?>
            }

          
        
        });
       
        //Inicializar el mapa
        var map;
        
        function initMap() {
            var lat=<?php echo e(old('lat',24.1583354)); ?>; 
            var lng=<?php echo e(old('lng',-110.3227886)); ?>;

            map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
            center: {lat: lat, lng: lng},
            streetViewControl: false,
            });

            marker = new google.maps.Marker({
                map: map,
                animation: google.maps.Animation.DROP,
                position: {lat: lat, lng: lng}
            });

            $("#lat").val(lat);
            $("#lng").val(lng);

            //Evento para dar clic en la mapa y dibjar la marca
            google.maps.event.addListener(map, "click", function (e) {

                latLng = e.latLng;
                                    
                // if marker exists and has a .setMap method, hide it
                if (marker && marker.setMap) 
                {
                    marker.setMap(null);
                }

                $("#lat").val(e.latLng.lat());
                $("#lng").val(e.latLng.lng());

                marker = new google.maps.Marker({
                    map: map,
                    animation: google.maps.Animation.DROP,
                    position: {lat: e.latLng.lat(), lng: e.latLng.lng()}
                });

                
            });

           
        }

        
        <?php if($errors->any()): ?> 
            form_prop=true;
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            Lobibox.notify('error', {
                        title: 'Error',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: "<?php echo e($error); ?>"
            });
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
       
        $(document).ready(function () 
        {  
            var modified;
            $("input, select").change(function () {   
                if($(this).val() == '')
                {
                    modified = false;  
                }else{
                    modified = true;  
                }
            });
            function validarCampos() {
            if (modified)  
                return confirm('Puede haber cambios sin guardar en el formulario, ¿Desea salir de todas formas?'); 
            }
            
            window.onbeforeunload = validarCampos;

        }); 

    </script>

    
    <?php $__env->stopPush(); ?>
    <?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/properties/create.blade.php ENDPATH**/ ?>