
    <div class="single-review-st-item res-mg-t-30 table-mg-t-pro-n">
        <div class="single-review-st-hd">
            <h2>Propiedades recientes</h2>
        </div>
        {{-- <div class="row"> --}}
                <hr>
                @if($properties->count() == 0)

                {{-- <div class="row"> --}}
                {{-- <div class="col-md-12"> --}}
                   
                        {{-- <div class="" align="center"> --}}
                                {{-- <h3></h3> --}}
                                <div class=" h-100 d-flex justify-content-center align-items-center">
                                    {{-- <div class="container theme-showcase" role="main"> --}}
                                           
                                        
                                         
                                            {{-- <div class="jumbob"> --}}
                                                
                                                    <h3 style="color:#777">Aún no tienes propiedades <i class="fa fa-sad-tear"></i></h3>
                                                    <p>Intenta agregar algunas</p>
                                                <a href="{{route('create.propertie')}}" class="btn btn-default">AGREGAR</a>
                                                {{-- </div>  --}}
                                           
                                          
                                {{-- </div> --}}
                                
                        </div>
                    {{-- </div> --}}
    
                {{-- </div> --}}
            {{-- </div> --}}

            @else

                <div style="text-align: justify;">
                    
                    @foreach($properties as $property)
                    <a href="{{ url('properties/view/'.$property->id.'-'.\Illuminate\Support\Str::slug($property->title)) }}">
                        <div class="single-review-st-text hoverq">
                        
                        <img src="{{$property->image}}" style="max-width:40%;">
                            <div class="review-ctn-hf">
                                <h3>{{ $property->title }}</h3>
                                <p>{{ $property->address }}</p>
                            </div>
                        </div>
                    </a>   
                    @endforeach

                </div>
                    @if($properties->count() >= 10)
                    <hr>
                    <div class="text-center">
                        <a href="{{ url('properties/index') }}">Ver más</a>
                    </div>
                    @endif
              
            @endif


           
            
        {{-- </div> --}}
      
    </div>
    

