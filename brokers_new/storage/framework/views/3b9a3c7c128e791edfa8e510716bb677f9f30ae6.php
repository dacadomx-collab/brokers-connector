<div class="row">

    <div class="col-md-6">
         <div class="row" style="margin-right: 0;margin-left: 0;"> 
            <div class="col-md-12 col-sm-12 col-xs-12">
                
                    <label for="">Identificador</label>
                    <input type="text" class="form-control" maxlength="15" name="key"
                        value="<?php echo e(old('key', $property->key)); ?>" placeholder="Ingresa una clave única para esta propiedad">
                        <input type="hidden" name="company_id" value="<?php echo e(Auth::user()->myCompany()->id); ?>">
                
            </div>
            
    
         </div> 



        <div class="col-md-12 col-xs-12 col-sm-12">
            <hr>

        </div>

        
            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                <div class="touchspin-inner">
                    <label style="font-weight:bold;">Número de pisos</label>
                    <input class="touchspin1" type="text" max="99" name="floor"
                        value="<?php echo e(old('floor', $data->floor)=="" ? '0' : old('floor', $data->floor)); ?>">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="" style="margin: 0px 0px 10px 0;">
                    <label for="">Piso ubicado</label>
                    <input type="number" placeholder="Piso en el que se ubica" class="form-control" min="0" max="100"
                        name="floor_ubicated"
                        value="<?php echo e(old('floor_ubicated', $data->floor_ubicated)==0 ? '' : old('floor_ubicated', $data->floor_ubicated)); ?>">
                </div>
            </div>

     


         

            <div class="form-group col-lg-3 col-md-6 col-sm-6 col-xs-6">
                <div class="touchspin-inner" style="text-align:center!important;">
                    <label style="font-weight:bold;">Habitaciones</label>
                    <input class="touchspin1" type="text" name="bedrooms" value="<?php echo e(old('bedrooms', $data->bedrooms)=="" ? '0' : old('bedrooms', $data->bedrooms)); ?>">
                </div>
            </div>
            <div class="form-group col-lg-3 col-md-6 col-sm-6 col-xs-6">
                <div class="touchspin-inner" style="text-align:center!important;">
                    <label style="font-weight:bold;">Baños</label>
                    <input class="touchspin1" type="text" maxlength="3" name="baths" value="<?php echo e(old('baths',$data->baths)=="" ? '0' : old('baths',$data->baths)); ?>">
                </div>
            </div>
            <div class="form-group col-lg-3 col-md-6 col-sm-6 col-xs-6">
                <div class="touchspin-inner" style="text-align:center!important;">
                    <label style="font-weight:bold;">1/2 Baños</label>
                    <input class="touchspin1" type="text" maxlength="3" name="medium_baths"
                        value="<?php echo e(old('medium_baths',$data->medium_baths)=="" ? '0' : old('medium_baths',$data->medium_baths)); ?>">
                </div>
            </div>
            <div class="form-group col-lg-3 col-md-6 col-sm-6 col-xs-6">
                <div class="touchspin-inner" style="text-align:center!important;">
                    <label style="font-weight:bold;">Estacionamientos</label>
                    <input class="touchspin1" type="text" name="parking_lots" value="<?php echo e(old('parking_lots',$data->parking_lots)=="" ? '0' : old('parking_lots',$data->parking_lots)); ?>">
                </div>
            </div>
            
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" >
                <hr>
            </div>
            <div class="row" style="margin-right: 0;margin-left: 0;">
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                    <div class="" style="margin: 0px 0px 10px 0;">
                        <label for=""> Área construida </label>
                        <input type="number" placeholder="m²" class="form-control" min="0" max="9999999999"
                            name="built_area"
                            value="<?php echo e(old('built_area', $data->built_area)==0 ? '' : old('built_area', $data->built_area)); ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                    <div class="" style="margin: 0px 0px 10px 0;">
                        <label for=""> Área de terreno</label>
                        <input type="number" placeholder="m²" class="form-control" min="0" max="9999999999"
                            name="total_area"
                            value="<?php echo e(old('total_area', $data->total_area)==0 ? '': old('total_area', $data->total_area)); ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                    <div class="" style="margin: 0px 0px 10px 0;">
                        <label for=""> Frente</label>
                        <input type="number" placeholder="m²" class="form-control" min="0" max="9999999999" name="front"
                            value="<?php echo e(old('front', $data->front)==0 ? '' : old('front', $data->front)); ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-6">
                    <div class="" style="margin: 0px 0px 10px 0;">
                        <label for=""> Largo </label>
                        <input type="number" placeholder="m²" class="form-control" min="0" max="9999999999" name="length"
                            value="<?php echo e(old('length', $data->length)==0 ? '': old('length', $data->length)); ?>">
                    </div>
                </div>
            </div>
        
    </div>

    <div class="col-md-6">


        
            <div class="row" style="margin-right: 0;margin-left: 0;">

                <div class="col-xs-12">
                    <label for="">Fecha de construcción</label><br>
                    <div class="i-checks col-xs-4">
                        <label style="color:#777"><input type="radio"
                                <?php echo e($data->antiquity=="" || old("year")=="y_1" ? 'checked' : ''); ?> value="y_1" name="year">
                            <i></i>Sin fecha</label>
                    </div>
            
                    <div class="i-checks col-xs-4">
                        <label style="color:#777"><input type="radio"
                                <?php echo e($data->antiquity==$year_now || old("year")=="y_2" ? 'checked' : ''); ?> value="y_2" name="year">
                            <i></i>Año actual</label>
                    </div>
            
                    <div class="i-checks col-xs-4 cop2">
                        <label style="color:#777"><input type="radio" id="op2"
                                <?php echo e(($data->antiquity!=$year_now && $data->antiquity!="") || old("year")=="y_3"  ? 'checked' : ''); ?>

                                value="y_3" name="year"> <i></i>Ingresar año</label>
                        <br>
                        <input name="antique_year" type="text" id="year" minlength="4" maxlength="4"
                            value="<?php echo e(old('antique_year' , $data->antiquity)); ?>"
                            class="form-control  <?php echo e(old('year')=='y_3' || ($data->antiquity!=$year_now && $data->antiquity!="") ? '' : 'hidden'); ?>"
                            placeholder="Año">
                        <span id="error-year" class="hidden text-danger" style="font-weight: 700;">Este campo es requerido</span>
                    </div>
                </div>
            </div>

            
            <div class="col-md-12 col-xs-12 col-sm-12">
                <hr>
            </div>
        


        <div class="col-md-12">
            <label for="">Comisión</label>
        </div>
        
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
            <div class="form-group" style="margin: 0px 0px 10px 0;">
                <select name="type_commission" class="form-control" id="type_commission">
                    <option value="">Tipo de comisión</option>
                    <?php $__currentLoopData = config('app.commission'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($index != 0): ?>
                    <option <?php echo e($index == old('type_commission', $data->type_commission) ? 'selected' : ''); ?>

                        value="<?php echo e($index); ?>"><?php echo e(ucwords($item)); ?>

                    </option>
                    <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
        </div>
       
        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
            <div class="form-group" style="margin: 0px 0px 10px 0;">
                <input type="number" placeholder="Valor" min="0" class="form-control" name="commission"
                    id="commission" value="<?php echo e(old('commission', $data->commission)); ?>">
            </div>
        </div>
        <div class="col-md-12 col-xs-12 col-sm-12">
            <hr>
        </div>

        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
            <label for="">Compartir en <span style="color:red;"></span></label>
        </div>
        
        
            <?php $__currentLoopData = $stocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(Auth::user()->company->service->isAspiOrAmpi() && ($item['name']=="bbc_aspi" || $item['name']=="bbc_ampi")): ?>
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                <label for="<?php echo e($key); ?>">
                    <div><?php echo e($item['title']); ?></div>
                    <img src="<?php echo e($item['img']); ?>" style="width:100%;max-height:50px; object-fit: contain;">
                </label>
                <div class="i-checks">
                    <label>
                        <input type="checkbox" class="group-check" name="<?php echo e($item['name']); ?>" id="<?php echo e($key); ?>" value="1" 
                            <?php echo e($data[$item['name']] ? "checked" : ""); ?>

                        > <i></i>
                    </label>
                    <span style="font-size:18px;"></span>
                </div>
            </div>
            <?php endif; ?>

            <?php if( (Auth::user()->company->service->isGeneral() || Auth::user()->company->service->isAspiOrAmpi()) && $item['name']=="bbc_general"): ?>
                
           
            <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center">
                <label for="<?php echo e($key); ?>">
                    <div><?php echo e($item['title']); ?></div>
                    <img src="<?php echo e($item['img']); ?>" style="width:100%;max-height:50px; object-fit: contain;">
                </label>
                <div class="i-checks">
                    <label>
                        <input type="checkbox" class="group-check" name="<?php echo e($item['name']); ?>" id="<?php echo e($key); ?>" value="1" 
                            <?php echo e($data[$item['name']] ? "checked" : ""); ?>

                        > <i></i>
                    </label>
                    <span style="font-size:18px;"></span>
                </div>
            </div>
            <?php endif; ?>
           

           
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
           


    </div>
    <?php if(!$edit): ?>
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="margin-top:35px;">
        <button type="button" id="next-step-2" class="btn btn-primary waves-effect waves-light">Siguiente &nbsp;<i
                class="fa fa-chevron-right"></i></button>
    </div>
    <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\brokersconnect_dev\brokers_new\resources\views/properties/utils/tab-addicional.blade.php ENDPATH**/ ?>