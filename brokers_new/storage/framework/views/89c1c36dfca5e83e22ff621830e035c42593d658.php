
<div class="row">

    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="padding:30px;">
        <div class="review-content-section">
            <div class="i-checks " id="check-all">
                <label >
                <input type="checkbox" class="all" id="all-features"> <i></i>
                   Marcar todas
                </label>
            </div>
            <div class="row search" id="masonry">
                
                <?php $__currentLoopData = $features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
               
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 group-name item" style="margin-bottom:20px;">
                    
                    <div class="form-inline">
                        <div class="i-checks">
                            <label>
                            <input type="checkbox" class="group-check check-features" data-id="<?php echo e($item->id); ?>"> <i></i>
                            </label>
                            <span style="font-size:18px;"> <?php echo e(ucfirst($item->name)); ?> </span>
                        </div>
                    </div>

                    <?php $__currentLoopData = $item->children()->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item_feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 check-item">
                        
                       
                        <div class="i-checks">
                            <label>
                            <input type="checkbox" class="check-features groupChecks-<?php echo e($item->id); ?>" name="features[]" 
                            <?php echo e(in_array($item_feature->id, old("features", $features_id) ) ? 'checked' : ''); ?> 
                            value="<?php echo e($item_feature->id); ?>"> <i></i>
                                <?php echo e(ucfirst($item_feature->name)); ?>

                            </label>
                        </div>
                        
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              
                
               
                
                
            </div>
        </div>
    </div>
</div>

<br>
<hr>
<?php if(!$edit): ?>
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
        
            <button type="button" id="nextimages"
                class="btn btn-primary waves-effect waves-light">Guardar <?php if(!$property->title): ?>
                    y agregar imagenes
                <?php endif; ?></button>
        
        </div>
    </div>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
    <script>
    
    $('.group-check').on('ifChanged', function(event, from){
       
        var id=$(this).data("id");
        
        if (event.currentTarget.checked) 
        {
            $(".groupChecks-"+id+":visible").iCheck('check');
        }
        else
        {
            $(".groupChecks-"+id+":visible").iCheck('uncheck');
        }

   })

    //Marcas todas las caracteristicas
    $('#all-features').on('ifChanged', function(event, from){
       
            
        if (event.currentTarget.checked) 
        {
            $(".check-features:visible").iCheck('check');
        }
        else
        {
            $(".check-features:visible").iCheck('uncheck');
        }
 
    })

  
    </script>
<?php $__env->stopPush(); ?><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/properties/utils/tab-features.blade.php ENDPATH**/ ?>