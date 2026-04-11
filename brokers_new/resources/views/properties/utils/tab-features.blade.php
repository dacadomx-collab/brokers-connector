
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
                
                @foreach ($features as $item)
               
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12 group-name item" style="margin-bottom:20px;">
                    
                    <div class="form-inline">
                        <div class="i-checks">
                            <label>
                            <input type="checkbox" class="group-check check-features" data-id="{{$item->id}}"> <i></i>
                            </label>
                            <span style="font-size:18px;"> {{ucfirst($item->name)}} </span>
                        </div>
                    </div>

                    @foreach ($item->children()->get() as $item_feature)
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 check-item">
                        
                       
                        <div class="i-checks">
                            <label>
                            <input type="checkbox" class="check-features groupChecks-{{$item->id}}" name="features[]" 
                            {{ in_array($item_feature->id, old("features", $features_id) ) ? 'checked' : ''}} 
                            value="{{$item_feature->id}}"> <i></i>
                                {{ ucfirst($item_feature->name) }}
                            </label>
                        </div>
                        
                    </div>
                    @endforeach
                </div>
                
                @endforeach
              
                
               
                
                
            </div>
        </div>
    </div>
</div>

<br>
<hr>
@if (!$edit)
    <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
        
            <button type="button" id="nextimages"
                class="btn btn-primary waves-effect waves-light">Guardar @if (!$property->title)
                    y agregar imagenes
                @endif</button>
        
        </div>
    </div>
@endif

@push('scripts')
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
@endpush