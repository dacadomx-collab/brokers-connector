@foreach ($properties as $property)
<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12 " style="margin-bottom:30px; height: 600px;"
                id="row-property{{$property->id}}">
                
                <div class="courses-inner res-mg-b-30 card h-100 animated slideInLeft">
                    <input type="checkbox" name="checkbox_property[]" value="{{$property->id}}" class="checkbox_property">
                    <div class="courses-title text-center ">
                        <a class="click-view" href="#" data-url="/stock/view/{{$property->id.'-'.str_slug($property->title)}}">
                            <img style="height: 255px; width: 100%; object-fit:cover;border-top-right-radius: 30px;" onerror="this.onerror=null; this.src='/images/no-logo.jpg'"
                                src="{{$property->image}}" alt="">
                            <h2> 
                                {{mb_substr($property->title,0,32)}}&nbsp;
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
                    <div class="text-center row" style="color:#777; margin-bottom:3px;">
                    @isset($myPropertiesOn)
                        @if ($myPropertiesOn)
                            <strong>Publicado en: </strong> <br> 
                            @if ($property->bbc_general)
                            
                            <div class="col-md-4 col-centered">
                                <a href="{{ url('stock/index/search') }}?properties_show=1" style="color:gray;">
                                <img src="/images/logos/big.svg" style="width:100%; max-height:50px; object-fit: contain;">
                                General</a>
                                
                            </div>
                            @endif

                            @if ($property->bbc_aspi)
                            <div class="col-md-4 col-centered">
                                <a href="{{ url('stock/index/search') }}?properties_show=2" style="color:gray;">
                                <img src="/images/logos/aspi-logo.jpg" style="width:100%; max-height:50px; object-fit: contain;">
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
                        @else
                            <strong>Publicado por: </strong> 
                            <a href="{{route('view.stockCompany', $property->company->id)}}"> {{$property->company->name}} </a>
                        @endif
                    @else
                        <strong>Publicado por:</strong> 
                        <a href="{{route('view.stockCompany', $property->company->id)}}"> {{$property->company->name}} </a>  
                    @endisset


                    <hr>
                    <h4>Compartir por</h4>
                    <a class="form-inline" 
                    target="_blank"  href="https://api.whatsapp.com/send?text={!!$property->title!!} {!! str_replace('https', 'http', url('/')) !!}/stock/view/{!!$property->id!!}?contact_email={!! Auth::user()->email !!}"
                    > <img src="{{ asset('img/social/whatsapp.png') }}" style="width:30px; height:30px;"> WhatsApp
                </a>
                    </div>     
                </div>
            </div>
            @endforeach