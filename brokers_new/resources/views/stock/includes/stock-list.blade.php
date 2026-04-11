@if ($properties->count())
<div class="product-status mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-status-wrap">
                    {{-- <h4>Library List</h4> --}}
                    {{-- <div class="add-product">
                        <a href="#">Add Library</a>
                    </div> --}}
                    <div class="asset-inner">
                        <table>
                            <tr>
                                {{-- <th> 
                                    <div class="i-checks pull-left">
                                        <label><input type="checkbox"></label>
                                    </div>
                                </th> --}}
                                <th><label><input type="checkbox"  class="checkbox_property" id="seleccionar-todo"></label></th>
                                <th>Imagen</th>
                                <th>Titulo</th>
                                <th>Operacion</th>
                                <th>Tipo</th>
                                <th>Ciudad</th>
                                <th>Precio</th>
                                <th>Publicado por</th>
                                @isset($myPropertiesOn)
                                    @if ($myPropertiesOn)
                                        <th>Publicado en</th>
                                    @endif
                                @endisset
                                <th>Acciones</th>
                            </tr>
                            @foreach ($properties as $property)
                            <tr id="row-property{{$property->id}}">
                                
                               
                                {{-- <td>
                                    <div class="i-checks pull-left">
                                        <label><input type="checkbox"></label>
                                    </div>
                                </td> --}}
                                <td><input name="checkbox_property[]" type="checkbox"  class="checkbox_property"  value="{{$property->id}}"></td>
                                
                                <td><a style="cursor:pointer;" class="click-view" data-url="/stock/view/{{$property->id.'-'.str_slug($property->title)}}"><img src="{{$property->image}}" alt="" /></a></td>
                                <td><a style="cursor:pointer;" class="click-view" data-url="/stock/view/{{$property->id.'-'.str_slug($property->title)}}">{{mb_substr($property->title,0,32)}}</a></td>
                                <td>{{$property->status->name}}</td>
                                <td>{{ucfirst($property->type_prop)}}</td>
                                <td>{{$property->local->city->name}}</td>
                                <td>${{number_format($property->price,2)}}</td>
                                <td><a href="{{route('view.stockCompany', $property->company->id)}}"> {{$property->company->name}} </a></td>
                                @isset($myPropertiesOn)
                                @if ($myPropertiesOn)
                                <td>
                           
                                        @if ($property->bbc_general)
                                        
                                        <div class="col-md-4 col-centered">
                                            <a href="{{ url('stock/index/search') }}?properties_show=1" style="color:gray;">
                                            
                                            General</a>
                                            
                                        </div>
                                        @endif

                                        @if ($property->bbc_aspi)
                                        <div class="col-md-4 col-centered">
                                            <a href="{{ url('stock/index/search') }}?properties_show=2" style="color:gray;">
                                            
                                            ASPI</a>
                                        </div>
                                        @endif

                                        @if ($property->bbc_ampi)
                                    
                                        <div class="col-md-4 col-centered">
                                            <a href="{{ url('stock/index/search') }}?properties_show=2" style="color:gray;">
                                            <img src="/images/logos/ampi-logo.png" style="width:100%; max-height:50px; object-fit: contain;">
                                            AMPI</a>
                                        </div>
                                        @endif
                                    </td>
                                    
                                    @endif

                                @endisset
                                <td>
                                   
                                    <a role="button" style="margin-right:10px;" class="pd-setting-ed" data-toggle="tooltip" target="_blank" href="https://api.whatsapp.com/send?text={!!$property->title!!} {!! str_replace('https', 'http', url('/')) !!}/stock/view/{!!$property->id!!}?contact_email={!! Auth::user()->email !!}" 
                                        title="Compartir" ><i class="fa fa-whatsapp" aria-hidden="true"></i></a>
                                    
                                    <a class="click-view" data-url="/stock/view/{{$property->id.'-'.str_slug($property->title)}}" style="cursor: pointer;">
                                        <i class="fa fa-eye" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                           
                           
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
    <script>
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
    </script>
@endpush