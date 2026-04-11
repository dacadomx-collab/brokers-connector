<!DOCTYPE html>
<html lang="en">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <style>
            hr {
                border: 0;
                height: 0;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            }
          
            .col-xs-4{
                width: 31.7%;
            }
            .col-3{
                width: 33.3%;
                
            }

            .col-6{

                width: 50%;
            }
            
           
            .row{
                width: 100%;
            }
            h1, h2,h3,h4,div,p, h5{
                font-family: sans-serif;
            }

            #container {
                width:960px;
                height:600px;
                position:relative; /* Set as position relative to the IMG will move relative to this container */
            }
            
            
            th{
               
                width: 140px;
               
            }

            .th-text{
                padding: 0 0px;
                
            }

          
            
            .text-center{
                text-align: center !important;
            }
          
            .page_break { page-break-before: always; }
        </style>
    <title>Document</title>
</head>
<body>
   <div class="row">
       
       @if ($user)
           
        <table class="text-center" style="width: 100%;">
            <tr>
                <td class="col-3"><img src="{{asset($user->foto_avatar)}}"  style="max-width:100px;max-height:100px;" alt=""></td>
                <td class="col-3"> <div ><b>{{substr(ucwords($user->f_name), 0, 38)}}</b></div>
                    <div style="font-size:13px;">{{$user->title}}</div>
                    <div style="font-size:13px;">{{$user->phone}}</div>
                    <div style="font-size:13px;">{{$user->email}}</p>
                </td>
                <td class="col-3"><div><u>{{$property->company->name}}</u></div>
                    <p style="font-size:13px;">{{$property->company->address}}</p>
                    {{-- <p>{{$property->company->dominio}}</p> --}}
                </td>
                <td class="col-3"><img src="{{asset($property->company->logo)}}"  style="max-width:100px;max-height:100px;"  alt=""></td>
            </tr>
        </table>

        @else

        <table class="text-center" style="width: 100%;">
            <tr>

                <td class="col-6"><img src="{{asset($property->company->logo)}}"  style="max-width:100px;max-height:100px;"  alt=""></td>
                {{-- <td class="col-3">
                    

                        <u>Baja Sol Inmobiliaria</u>
                    
                </td> --}}

                {{-- <td class="col-3"><img src="{{asset($user->foto_avatar)}}"  style="max-width:100px;max-height:100px;" alt=""></td> --}}
                <td class="col-6"><div>
                    <div style="font-size:30px;">{{$property->company->name}}</div>
                    <p style="font-size:12px;">{{$property->company->address}}</p>
                    {{-- <p>{{$property->company->dominio}}</p></td> --}}
                    <p style="font-size:12px;">{{$property->company->phone}}</p>
                    <p style="font-size:12px;">{{$property->company->email}}</p></td>


                {{-- <td class="col-3"> <div >{{substr(ucwords($user->f_name), 0, 38)}}</div>
                    <p>{{$user->title}}</p>
                    <p>{{$user->phone}}</p>
                    <p>{{$user->email}}</p></td> --}}
            </tr>
        </table>
        
       @endif
        

         
   
<hr>
     @if ($property->title)
         
     <h2 class="text-center">{{$property->title}}</h2>
     @endif

     


      <table >

        @switch($style)
            @case(1)
            {{-- 1 imagenes de portada --}}
            <tr>
                <td><img style="height: 300px; width: 100%;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[0]}}?tr=w-700,h-300,cm-pad_resize,bg-F3F3F3" alt=""></td>
                
            </tr>
                @break
            @case(2)
            {{-- 2 imagenes de portada --}}
            <tr>
                <td><img style="height: 300px; width: 50%;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[0]}}?tr=w-350,h-300,c-maintain_ratio " alt=""></td>
                <td><img style="height: 300px; width: 50%" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[1]}}?tr=w-350,h-300,c-maintain_ratio" alt=""></td>
            </tr>
                @break
            @case(4)
            {{-- 4 imagenes de portada --}}
            <tr>
                <td><img style="height: 300px; width: 75%;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[0]}}?tr=w-525,h-300,c-maintain_ratio" alt=""></td>
                
                   
                        <td><img style="height: 73px; width: 25%;margin-bottom:2px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[1]}}?tr=w-175,h-73,c-maintain_ratio" alt=""><br>
                            <img style="height: 73px; width: 25%;margin-bottom:2px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[2]}}?tr=w-175,h-73,c-maintain_ratio" alt=""><br>  
                            <img style="height: 73px; width: 25%;margin-bottom:2px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[3]}}?tr=w-175,h-73,c-maintain_ratio" alt=""><br>  
                            <img style="height: 73px; width: 25%;margin-bottom:2px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[4]}}?tr=w-175,h-73,c-maintain_ratio" alt=""><br>  
                        
                        </td>
                    
               
            </tr>
                @break
            @case(8)
            {{-- 5 imagenes de portada --}}
            <tr>
                <td><img style="height: 300px; width: 530px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[0]}}" alt=""></td>
                        <td><img style="height: 73px; width: 170px;margin-bottom:3px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[1]}}?tr=w-170,h-73,c-maintain_ratio" alt=""><br>
                            <img style="height: 73px; width: 170px;margin-bottom:3px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[2]}}?tr=w-170,h-73,c-maintain_ratio" alt=""><br>  
                            <img style="height: 73px; width: 170px;margin-bottom:3px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[3]}}?tr=w-170,h-73,c-maintain_ratio" alt=""><br>  
                            <img style="height: 73px; width: 170px;margin-bottom:0px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[4]}}?tr=w-170,h-73,c-maintain_ratio" alt=""><br>  
                        </td>
           
              
            </tr>
         
            <tr>
                <td><img style="height: 73px; width: 174px;padding:0;margin-top:8px;margin-left:0px" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[5]}}?tr=w-170,h-73,c-maintain_ratio" alt="">
                    <img style="height: 73px; width: 174px;padding:0;margin-top:8px;margin-left:4px" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[6]}}?tr=w-170,h-73,c-maintain_ratio" alt=""> 
                    <img style="height: 73px; width: 174px;padding:0;margin-top:8px;margin-left:4px" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[7]}}?tr=w-170,h-73,c-maintain_ratio" alt=""> 
                    <img style="height: 73px; width: 174px;padding:0;margin-top:8px;margin-left:4px;" src="https://ik.imagekit.io/zcr5u0oqc0b/{{$images[8]}}?tr=w-170,h-73,c-maintain_ratio" alt=""> 
                </td>
            </tr>
                @break
            @default
                
        @endswitch
        
       
        {{--  1 imagen de portada
        <tr>
            <td><img style="height: 250px; width: 100%;" src="https://picsum.photos/800/500" alt=""></td> 
        </tr> --}}

          {{-- <tr>
            @if ($property->images()->count() > 1)

              @if ($property->featured_img)
                  <td class="text-center" style="width: 400px;background:white; border:none;" ><img width="350" height="250" style="object-fit:cover;transform: translateX(10px);" src="https://ik.imagekit.io/zcr5u0oqc0b{{$property->featured_img->src}}?tr=w-350,h-250,cm-pad_resize,bg-F3F3F3" alt=""></td>

                    @if ($property->featured_img->id != $property->images()[0]->id)
                    <td class="text-center" style="width: 382.5px;background:white; border: :none;" ><img width="350" height="250" style="object-fit:cover!important;transform: translateX(-10px);" src="https://ik.imagekit.io/zcr5u0oqc0b{{$property->images()[0]->src}}?tr=w-350,h-250,cm-pad_resize,bg-F3F3F3" alt=""></td>

                    @else
                    <td class="text-center" style="width: 382.5px;background:white; border: :none;" ><img width="350" height="250" style="object-fit:cover!important;transform: translateX(-10px);" src="https://ik.imagekit.io/zcr5u0oqc0b{{$property->images()[1]->src}}?tr=w-350,h-250,cm-pad_resize,bg-F3F3F3" alt=""></td>

                    @endif

              @else
                <td class="text-center" style="width: 382.5px;background:white; border: :none;" ><img width="350" height="250" style="object-fit:cover!important;transform: translateX(-10px);" src="https://ik.imagekit.io/zcr5u0oqc0b{{$property->images()[0]->src}}?tr=w-350,h-250,cm-pad_resize,bg-F3F3F3" alt=""></td>
                <td class="text-center" style="width: 382.5px;background:white; border: :none;" ><img width="350" height="250" style="object-fit:cover!important;transform: translateX(-10px);" src="https://ik.imagekit.io/zcr5u0oqc0b{{$property->images()[1]->src}}?tr=w-350,h-250,cm-pad_resize,bg-F3F3F3" alt=""></td>

              @endif
            
            @elseif($property->images()->count() == 1)
           
                <td class="text-center" style="width: 765px;background:white; border: :none;" ><img width="700" height="250" style="object-fit:cover!important;transform: translateX(-10px);" src="https://ik.imagekit.io/zcr5u0oqc0b{{$property->images()[0]->src}}?tr=w-700,h-250,cm-pad_resize,bg-F3F3F3" alt=""></td>

            @else
            <td class="text-center" style="width: 765px;background:white; border: :none;" ><img width="700" height="250" style="object-fit:cover!important;transform: translateX(-10px);" src="https://ik.imagekit.io/zcr5u0oqc0b/images/no-logo.jpg?tr=w-700,h-250,cm-pad_resize" alt=""></td>

            @endif
            </tr> --}}
      </table>
    
   
    <h1 style="text-align:center; font-weight:bold;">
            ${{number_format($property->price)}} <span style="font-size:15px">{{$property->CurrencyAttr}}</span>
         </h1>
    <h4 class="text-center" style="color:#777"><span>{{$property->status_name}} / {{ucfirst($property->type_prop)}}</span></h4>
    
    
    @if (in_array("adtional1", $show))

            @if ($property->bedrooms || $property->baths_count || $property->parking_lot|| $property->total_area)
            <hr>

            <table class="text-center" >


            <thead>
                <tr>
                    @if ($property->bedrooms)
                        
                    <th class="text-center" > <img  src="{{asset('icons/bed.png')}}" width="40px"></th>
                    @endif

                    @if ($property->baths_count)

                    <th class="text-center" > <img  src="{{asset('icons/bath.png')}}" width="40px"></th>
                    @endif

                    @if ($property->parking_lots)

                    <th class="text-center" > <img  src="{{asset('icons/car.png')}}" width="40px"></th>
                    @endif

                    @if ($property->total_area)

                    <th class="text-center" > <img  src="{{asset('icons/area.png')}}" width="40px"></th>
                    @endif
                    @if ($property->built_area)

                    <th class="text-center" > <img  src="{{asset('icons/built.png')}}" width="40px"></th>
                    @endif
            
                    </tr>
            </thead>

            <tbody>
                <tr>
                    @if ($property->bedrooms)
                        
                    <th class="text-center th-text" ><span style="font-size:20px">{{$property->bedrooms}}</span><br>
                        <span style="color:#777;" >Recámara{{$property->bedrooms>1 ? 's' : ''}}</span>
                    </th>
                    @endif
                    @if ($property->baths_count)
                        
                    <th class="text-center th-text" ><span style="font-size:20px">{{$property->baths_count}}</span><br>
                        <span style="color:#777;" >Baño{{$property->baths_count>1 ? 's' : ''}}</span>
                    </th>
                    @endif
                    @if ($property->parking_lots)
                        
                    <th class="text-center th-text" ><span style="font-size:20px">{{$property->parking_lots}}</span><br>
                        <span style="color:#777;" >Estacionamiento{{$property->parking_lots>1 ? 's' : ''}}</span>
                    </th>
                    @endif
                    @if ($property->total_area)
                    
                    <th class="text-center th-text" ><span style="font-size:20px">{{$property->total_area}} m²</span><br>
                        <span style="color:#777;" >Área Total</span>
                    </th>
                    @endif
                    @if ($property->built_area)
                    
                    <th class="text-center th-text" ><span style="font-size:20px">{{$property->built_area}} m²</span><br>
                        <span style="color:#777;" >Área Construida</span>
                    </th>
                    @endif
                    
                    
                </tr>
            </tbody>
            </table>
            @endif
    @endif


    
    @if (in_array("adtional2", $show))
    <hr>
        @if ( $property->front || $property->length || $property->floor || $property->antiquity)
        <table >
            <tr>   
                @if ($property->front)    
                <td class="text-center extra"> • Frente: {{$property->front}}m</td>
                @endif
                @if ($property->length)
                <td class="text-center extra"> • Largo: {{$property->length}}m</td>
                @endif
                @if ($property->floor)
                <td class="text-center extra"> • No. de pisos: {{$property->floor}}</td>
                @endif
                @if ($property->antiquity)
                <td class="text-center extra"> • Año de construcción: <b>{{$property->antiquity}}</b></td>
                @endif
            </tr>

        </table>

        @endif
@endif

    
<div>
    @if ($property->description)
        
    <h3><u>Descripción</u></h3>
    <p>{{$property->description}}</p>
    @endif
    
    @if (in_array("features", $show))
        @if ($property->features->count())
            
        <h3><u>Características</u></h3>
        
        @foreach ($property->features as $item)
            <span>
            • {{ucwords($item->name)}}
            </span>
        @endforeach
        @endif
    @endif

    @if (in_array("ubication", $show))
    <hr>
    <h3><u>Ubicación</u></h3>

    <p>{{$property->address}} {{$property->exterior}}, {{$property->local->name}}, {{$property->local->city->name}} {{$property->zipcode}}, 
        {{$property->local->city->state->name}}, México.</p>
    @endif
  
      @if (in_array("map", $show))
      <img style="width:700px; height: 300px" src="https://maps.googleapis.com/maps/api/staticmap?center={!! $property->lat !!},{!!$property->lng!!}&zoom=15&size=765x300&markers=color:red%7Clabel:•%7C{!! $property->lat !!},{!!$property->lng!!}&key=AIzaSyCd-nS2-__7zMOypXiB_EJC53l-8s1cg84" alt="">
      @endif
    

  

  
</div>
</body>
</html>