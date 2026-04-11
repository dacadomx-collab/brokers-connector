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
                                <th>Clave</th>
                                <th>Imagen</th>
                                <th>Titulo</th>
                                <th>Operacion</th>
                                <th>Tipo</th>
                                <th>Ciudad</th>
                                <th>Precio</th>
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
                                <td>{{$property->key}}</td>
                                <td><a href="/properties/view/{{$property->id.'-'.str_slug($property->title)}}"><img src="{{$property->image}}" alt="" /></a></td>
                                <td><a href="/properties/view/{{$property->id.'-'.str_slug($property->title)}}">{{mb_substr($property->title,0,32)}}</a></td>
                                <td>{{$property->status->name}}</td>
                                <td>{{ucfirst($property->type_prop)}}</td>
                                <td>{{$property->local->city->name}}</td>
                                <td>${{number_format($property->price,2)}}</td>
                                <td>
                                    <span class="status-btn" data-id="{{$property->id}}" data-published="{{$property->published}}">
                                    <button  type="button" data-toggle="tooltip" title="{{$property->published ? 'Despublicar' : 'Publicar'}}" class="pd-setting-ed">
                                            <i class="fa {{$property->published ? ' fa-check text-success' : 'fa-times text-danger'}}" aria-hidden="true"></i>
                                        </button>
                                    </span>
                                    @role('Admin')
                                    <button type="button" data-toggle="tooltip" title="{{$property->featured ? 'Remover destacado' : 'Destacado'}}" class="pd-setting-ed">
                                        @if($property->featured)
                                        <a class="star star-active star-list" data-id="{{$property->id}}"><i
                                            class="fa fa-star"></i> </a>
                                        @else
                                        <a class="star star-list" data-id="{{$property->id}}"><i
                                            class="fa fa-star-o"></i> </a>
                                        @endif
                                    </button>
                                    @endrole
                                    
                                    <button type="button" data-toggle="tooltip" data-link="{{route('show.edit.properties', $property->id)}}" title="Editar" class="pd-setting-ed button-edit"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></button>
                                    <button  type="button" data-toggle="tooltip" data-property="{{$property->id}}" title="Eliminar" class="pd-setting-ed delete-property"><i class="fa fa-trash-o" aria-hidden="true"></i></button>
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