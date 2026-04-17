<form action="<?php echo e($url); ?>" method="get">
<div class="container-fluid">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div style="padding-top: 20px;background: #fff;padding-left: 20px;padding-right: 20px;margin-bottom: 15px;padding-bottom: 20px;">
                <div class="row">
                    <div class="col-lg-10 col-md-10 col-sm-10 col-xs-12">
                        <div class="row">
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="status_select">Operación</label>
                                <select class="form-control" name="status" id="status_select"
                                    style="width:100%;">
                                    <option value="">Elegir</option>
                                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option
                                        <?php if(isset($old_inputs, $old_inputs['status'])): ?><?php echo e($old_inputs['status']==$status->id ? "selected" :  ""); ?><?php endif; ?>
                                        value="<?php echo e($status->id); ?>"><?php echo e(ucfirst($status->name)); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
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
    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="price">Precio min.</label><br>
                                <input type="text" class="form-control price" placeholder="Min" name="price_min"
                                    id="price" max="9999999999" style="width:100%;"
                                    value="<?php if(isset($old_inputs, $old_inputs['price_min'])): ?><?php echo e($old_inputs['price_min']); ?><?php endif; ?>">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="price">Precio max.</label><br>
                                <input type="text" class="form-control price" placeholder="Max" name="price_max"
                                    max="9999999999" style="width:100%;"
                                    value="<?php if(isset($old_inputs, $old_inputs['price_max'])): ?><?php echo e($old_inputs['price_max']); ?><?php endif; ?>">
                            </div>
    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="state">Estado</label>
                                <select  class="form-control" name="state"  id="states" style="width:100%;"></select> 
                            </div>

                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="city">Ciudad</label>
                                <select  class="form-control" name="city"  id="cities" style="width:100%;"></select> 
                            </div>
                            
                        </div>

                        <div class="row ">
                            <div class="col-lg-2">
                                <button id="button-advanced" type="button" class="btn btn-default"
                                    style="margin-top:25px; width:-webkit-fill-available">Más filtros</button>
                            </div>
                           
                        </div>
                      
                        <div class="row" id="advanced"
                            style="display:<?php if(isset($old_inputs)): ?><?php echo e($btn_more_display ? 'block' : 'none'); ?><?php else: ?> none <?php endif; ?>; margin-top: 15px;">
    
                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-12">
                                <label for="search">Buscar por nombre</label>
                                <input type="text" class="form-control" placeholder="Ingresa el nombre"
                                    name="search" id="search" style="width:100%;"
                                    value="<?php if(isset($old_inputs, $old_inputs['search'])): ?><?php echo e($old_inputs['search']); ?><?php endif; ?>">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="parking">Estacionamientos</label>
                                <input type="number" class="form-control" placeholder="Cantidad" min="0"
                                    name="parking" id="parking" style="width:100%;"
                                    value="<?php if(isset($old_inputs, $old_inputs['parking'])): ?><?php echo e($old_inputs['parking']); ?><?php endif; ?>">
                            </div>
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="baths">Baños</label>
                                <input type="number" class="form-control" name="baths" placeholder="Cantidad"
                                    min="0" id="baths" style="width:100%;"
                                    value="<?php if(isset($old_inputs, $old_inputs['baths'])): ?><?php echo e($old_inputs['baths']); ?><?php endif; ?>">
                            </div>
    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                                <label for="room">Habitaciones</label>
                                <input type="number" class="form-control" name="rooms" placeholder="Cantidad"
                                    min="0" id="room" style="width:100%;"
                                    value="<?php if(isset($old_inputs, $old_inputs['rooms'])): ?><?php echo e($old_inputs['rooms']); ?><?php endif; ?>">
                            </div>
                        </div>
    
                        <div class="row" style="margin-top:10px;">
                            
    
    
                        </div>
    
                    </div>
                    <div class="col-lg-2 col-md-2 col-sm-2 col-xs-12">
                        
                        <button class="btn btn-primary" style="width:-webkit-fill-available; margin-top: 25px;">
                            <i class="fa fa-search" aria-hidden="true"></i>
                        </button>
                        <?php if(isset($old_inputs)): ?>
                        <a class="text-center" href="<?php echo e($url_clean); ?>">Limpiar campos</a>
                        <?php endif; ?>
                    <br><br>
                        
                    </div>
                   
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="row">
                            <div class="col-lg-1 col-md-1 col-sm-1 col-xs-6 pull-right">
                                <?php if(Session::has('view-stock')): ?>

                                    <a id="view" style="min-height:40px;"
                                        href="/stock/change-view?value=<?php echo e(Session::get('view-stock') ? '0' : '1'); ?>"
                                        data-toggle="tooltip" title="Cambiar vista" class="btn btn-custon-four">
                                        <i style="vertical-align: middle;"
                                            class="fa <?php echo e(Session::get('view-stock') ? 'fa-th-large' : 'fa-list'); ?>"></i></a>
                                <?php else: ?>
                                    <a id="view" style="min-height:40px;" href="<?php echo e(url('stock/change-view')); ?>?value=1"
                                        data-toggle="tooltip" title="Cambiar vista" class="btn btn-custon-four">
                                        <i style="vertical-align: middle;" class="fa fa-list"></i></a>
                                <?php endif; ?>
                            </div>
                            

                            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6 pull-right">
                    
                                <select class="form-control" name="properties_show" onchange="this.form.submit()">

                                  
                                    <option <?php if(isset($old_inputs)): ?><?php echo e($old_inputs['properties_show']==1 ? "selected" : ""); ?><?php endif; ?> value="1">Bolsa Inmobliliaria</option>                         
                                    

                                    <?php if($service_adquired->isAspiOrAmpi()): ?>
                                        <option <?php if(isset($old_inputs)): ?><?php echo e($old_inputs['properties_show']==2 ? "selected" : ""); ?><?php endif; ?> value="2">Bolsa ASPI</option>
                                        <option <?php if(isset($old_inputs)): ?><?php echo e($old_inputs['properties_show']==3 ? "selected" : ""); ?><?php endif; ?> value="3">Bolsa AMPI</option>
                                    <?php endif; ?>

                                 
                                        <option <?php if(isset($old_inputs)): ?><?php echo e($old_inputs['properties_show']==4 ? "selected" : ""); ?><?php endif; ?> value="4">Mis propiedades</option>
                                  
                                        
                                </select>
                            </div>
                          
                    
                            <div class="col-lg-2 col-md-2 col-sm-2 col-xs-6 pull-right">
                                    
                                <select class="form-control" name="order" onchange="this.form.submit()">
                                    <option value="">Ordenar por</option>
                                    
                                    <option <?php if(isset($old_inputs, $old_inputs['order'] )): ?><?php echo e($old_inputs['order']==1 ? "selected" : ""); ?><?php endif; ?> value="1">Precio mas bajo</option>
                                    <option <?php if(isset($old_inputs, $old_inputs['order'] )): ?><?php echo e($old_inputs['order']==2 ? "selected" : ""); ?><?php endif; ?> value="2">Precio mas alto</option>
                                    <option <?php if(isset($old_inputs, $old_inputs['order'] )): ?><?php echo e($old_inputs['order']==3 ? "selected" : ""); ?><?php endif; ?> value="3">Titulo ascendente (A-Z)</option>
                                    <option <?php if(isset($old_inputs, $old_inputs['order'] )): ?><?php echo e($old_inputs['order']==4 ? "selected" : ""); ?><?php endif; ?> value="4">Titulo descendente (Z-A)</option>
                                    <option <?php if(isset($old_inputs, $old_inputs['order'] )): ?><?php echo e($old_inputs['order']==5 ? "selected" : ""); ?><?php endif; ?> value="5">Mas recientes</option>
                                    <option <?php if(isset($old_inputs, $old_inputs['order'] )): ?><?php echo e($old_inputs['order']==6 ? "selected" : ""); ?><?php endif; ?> value="6">Mas antiguos</option>
                                    
                                </select>

                            </div>

                           
                        </div>
                    </div>
                </div>
    
           
        </div>
    </div>
</div>
    
    </form><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/stock/includes/filter.blade.php ENDPATH**/ ?>