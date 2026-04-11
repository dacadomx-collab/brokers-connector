
@foreach ($properties as $property)
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12 " style="margin-bottom:30px; "
                id="row-property{{$property->id}}">
                <div class="courses-inner res-mg-b-30 card h-100 animated slideInLeft">
                    <input type="checkbox" name="checkbox_property[]" value="{{$property->id}}" class="checkbox_property">
                    @role('Admin')
                    @if($property->featured)
                    <a class="star star-active" data-id="{{$property->id}}"><i
                            class="fa fa-2x fa-star"></i> </a>
                    @else
                    <a class="star" data-id="{{$property->id}}"><i
                            class="fa fa-2x fa-star-o"></i> </a>
                    @endif
                    @endrole
                    <div class="courses-title text-center ">
                        <a href="/properties/view/{{$property->id.'-'.str_slug($property->title)}}">
                            <img style="height: 255px;width: 100%; object-fit:cover;border-top-right-radius: 30px;border-top-left-radius: 30px;"
                                src="{{$property->image}}" alt="">
                            <h2> {{mb_substr($property->title,0,32)}}&nbsp; <i class="fa fa-circle"
                                    data-toggle="tooltip"
                                    title="{{$property->published ? 'Publicado' : 'No publicado' }}"
                                    style="font-size:11px;vertical-align: middle; color:{{$property->published ? '#34a854' : '#ea4331' }}"></i>
                            </h2>
                        </a>
                        <div style="color:#777; margin-bottom:3px;"> {{$property->local->city->name}}</div>
                    </div>
                    <div class="courses-alaltic text-center">
                        <span class="cr-ic-r"><span class="course-icon"><i class="fa fa-bed"></i></span>
                            {{$property->bedrooms}}</span>
                        <span class="cr-ic-r"><span class="course-icon"><i class="fa fa-bath"></i></span>
                            {{$property->baths_count}}</span>
                        <span class="cr-ic-r"><span class="course-icon"><i class="fa fa-car"></i></span>
                            {{$property->parking_lots}}</span>
                    </div>
                    <div class="course-des">
                        <div class="row">
                            <div class="col-md-1 col-lg-1 col-sm-1 col-xs-1 pull-right dropup" style="padding:0px">
                                <a class="dropdown-toggle" data-toggle="dropdown" href="#" style="padding:0 10px;">
                                    <i class="fa fa-ellipsis-v fa-2x" aria-hidden="true"></i>
                                </a>
                                <ul class="dropdown-menu w-100 fadeInDown" role="menu">
                                    <li>
                                        <a href="{{route('show.edit.properties', $property->id)}}">
                                            <i class="fa fa-pencil fa-lg" aria-hidden="true"></i>&nbsp; Editar
                                            propiedad</a></li>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="{{route('add.images.properties', $property->id)}}">
                                            <i class="fa fa-camera fa-lg" aria-hidden="true"></i>&nbsp; Añadir fotos y
                                            video</a>
                                    </li>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="#" class="delete-property" data-property="{{$property->id}}">
                                            <i class="fa fa-times fa-lg" style="color: #e12503"
                                                aria-hidden="true"></i>&nbsp; Eliminar propiedad</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6 col-lg-6 col-sm-6 col-xs-6">
                                <p><span><i class="fa fa-dollar-sign"></i></span><b>Precio:</b>
                                    {{number_format($property->price,2)}} {{$property->currency_attr}}</p>
                                <p> <b>Estado:</b> {{$property->status->name}} </p>
                            </div>
                            <div class="col-md-5 col-lg-5 col-sm-5 col-xs-5">
                                <p> <b>Área construida:<br></b> {{$property->built_area ? $property->built_area : '?'}}
                                    m²</p>
                                <p> <b>Superficie total:<br></b> {{$property->total_area ? $property->total_area : '?'}}
                                    m²
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach