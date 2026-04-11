@extends('layouts.app')
@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Agregar imagenes</span>
</li>
@endsection
@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('css/dropzone.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/modals.css') }}">
{{--  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightgallery/1.3.2/css/lightgallery.css?version={{ config('app.version')}}" />  --}}
<link rel="stylesheet" href="{{ asset('admin/css/magnific-popup.css') }}">
<style>
    /* {
            {
            -- .dropzone.dz-clickable .dz-message {
                margin: 2em 0 !important;
                --
            }
        } */

        .header-top-area{
            position: absolute;
        }
        .mfp-zoom-out-cur {
        cursor: pointer;
        }
        .mfp-zoom-out-cur .mfp-image-holder .mfp-close {
        cursor: pointer;
        }


        .container-image {
            position: relative;
            width: 100%;
            height: 250px;
            /*box-shadow: 2px 9px 20px;*/
          /*  cursor: pointer;*/
           /* margin-bottom: 25px;*/
         
          }

          .select-image{
            background: #3077b7;
            padding: 10px;
          }
          
          .image {
              display: block;
              width: 100%;
              height: -webkit-fill-available;
              object-fit: contain;
            }
          
          .overlay {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100%;
            width: 100%;
            opacity: 1;
            transition: .3s ease;
            background-color:transparent;
            cursor: pointer;
          
          }

          .container-image:hover .overlay {
            opacity: 1;
          }
          
          .text {
            color: white!important;
            //font-size: medium;
            
            position: absolute;
            width:100%;
            top: 50%;
            left: 50%;
            -webkit-transform: translate(-50%, -50%);
            -ms-transform: translate(-50%, -50%);
            transform: translate(-50%, -50%);
            text-align: center;
          }

          .options{
          
            position: fixed;
            z-index: 100;
            width:100%;
            background: #3077b7;
            color: white;
            top:0;
            margin-top:0px;
            display:none;
            padding: 13px 20px;
          }

          .btn-transparent{
            color: #ffffff;
            background-color: #fff0;
            border-color: #ffffff;
            min-width: 150px;
            margin: 0 10px;
          }
          .btn-transparent:hover{
            color: #000;
            background-color: white;
            border-color:#3077b7;
           
          }
          .btn-transparent:hover i.fa-star{
           color: #ff9800 !important;
           
          }
          .btn-transparent:hover i.fa-eye{
           color: #3077b7 !important;
           
          }
          .btn-transparent:hover i.fa-times{
           color: #f44336 !important;
           
          }

          .featured{
           position: absolute;
           z-index: 10;
           margin: 2px 22px;
           right:0;
           color: #ffeb3b;
           font-size: x-large;
          }
          .handle{
           position: absolute;
           z-index: 10;
           margin: 2px 22px;
           left:0;
           color: aliceblue;
           font-size: x-large;
           cursor: move;
          }
          
          .drag-image{
            opacity: 1.25;
          }
</style>
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">

@endpush
@section('content')
<input type="hidden" id="property_id" value="{{$property->id}}">

<div id="options" class="options">
    <div class="row">
        <div class="col-xs-11">
            {{-- <h4>Opciones</h4> --}}
            <button type="button" class="btn btn-delete-file btn-transparent"><i class="fa fa-times"
                    aria-hidden="true"></i> Eliminar</button>
            <span id="options-single">
                {{--  <button type="button" class="btn btn-transparent"><i class="fa fa-pencil" aria-hidden="true"></i>
                    Editar</button>  --}}
                <button type="button" class="btn btn-open btn-transparent"><i class="fa fa-eye" aria-hidden="true"></i>
                    Ver</button>
                <button type="button" class="btn btn-featured btn-transparent"><i class="fa fa-star"
                        aria-hidden="true"></i> Destacado</button>
            </span>

        </div>
    </div>
</div>

<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">
        <div class="row">
            <div class=" col-xs-12">
                <div class="product-payment-inner-st">
                    <h3>Sube multimedia a <a
                            href="/properties/view/{{$property->id.'-'.str_slug($property->title)}}"
                            style="color:#3077b7">{{$property->title}}</a> </h3>

                    <div class=" custom-product-edit">
                        <div class="product-tab-list tab-pane fade active in" id="description">
                            <div class="row">
                                <div class=" col-xs-12">
                                    <div class="review-content-section">
                                        {{--  <div id="dropzone1" class="">  --}}
                                        {{--  <form action="{{url('upload/store')}}" enctype="multipart/form-data"
                                        class="dropzone dropzone-custom needsclick addcourse" id="demo1-upload"> --}}

                                        <div class="row">

                                            <div class="col-md-12">
                                                <form action="{{ url('files/upload/store') }}" class="dropzone" method="POST">
                                                    @csrf
                                                    {{-- <input type="hidden" name="type" value="1"> --}}
                                                    <input type="hidden" name="property_id" value="{{$property->id}}">
                                                    <div class="form-group alert-up-pd">
                                                        <div class="dz-message needsclick download-custom">
                                                            <i class="fa fa-download edudropnone"
                                                                aria-hidden="true"></i>
                                                            <h2 class="edudropnone">
                                                                Máximo 20 imagenes y un video, arrastra o haz
                                                                click aqui para cargarlos</h2>
                                                            {{--  <p class="edudropnone"><span class="note needsclick">(Maximo 8 MB)</span>  --}}
                                                            </p>
                                                            <input name="imageico" class="hd-pro-img" type="text" />
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>

                                        </div>
                                        <hr>

                                        <div class="row text-left animated fadeIn" id="div-images">
                                            <div class=" col-xs-12" style="margin-bottom:5px;">
                                                <span style="font-size:25px;"><i class="fa fa-picture-o" aria-hidden="true"></i> Imágenes </span> <span class="h5">(Arrastre <i class="fa fa-arrows"></i> para ordenar)</span>
                                            </div>
                                           
                                        
                                   
                                        <div id="no-imagenes" style="@if($property->images()->count() == 0) display: block; @else display:none; @endif">
                                            
                                            <div class="col-md-12" align="center">
                                                <img src="/images/no_file.JPG" alt="" style ="  height: 200px; width: 200px; padding-bottom: 25px;">
                                                <h4>Ooops! No tienes imagenes</h4>
                                                <p>Recuerda que una imagen vale mas que mil palabras.</p>
                                            </div>
                                        </div>

                                        <div id="grid">

                                            @foreach ($property->images() as $image)
                                            <div class="col-lg-3 col-md-4 col-sm-4 col-xs-12 animated fadeIn div-img images-div" id="div-file-{{$image->id}}" style="margin-bottom:15px;">
                                                {{--  <h2>{{$image->index_order}}</h2>  --}}
                                                <div class="featured animated bounceIn"
                                                    style="display: {{$property->featured_image == $image->id ? 'block' : 'none'}}">
                                                    <i style="text-shadow: 0 0 20px black;" class="fa fa-star"></i>
                                                </div>
                                                <div class="handle">
                                                    <i style="text-shadow: 0 0 20px black;" class="fa fa-arrows"></i>
                                                </div>
                                                <div class="container-image" data-id="{{$image->id}}">
                                                    <div style="background:white;">
                                                        <a href="{{$image->src}}"><img class="img" src="{{$image->thumbnail}}" style="height: -webkit-fill-available;width: -webkit-fill-available;object-fit:cover;"></a>
                                                    </div>  
                                                    <div class="overlay"></div>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>



                                    </div>
                                </div>
                               <hr>
                                <div class="row animated fadeIn" id="div-yt" style="max-height:300px">
                                    <div class=" col-xs-12" style="margin-bottom:20px;">
                                        <div>
                                            <span style="font-size:25px;"><i class="fa fa-video-camera" aria-hidden="true"></i> Videos</span>
                                            <a class="btn btn-default" data-toggle="modal" data-target="#add_yt" style="margin-left:20px; cursor:pointer; float:right;">
                                                    <i class="fa fa-plus" aria-hidden="true"></i> Agregar videos</a>
                                        </div>
                                    </div>
                                    
                                    <div id="no-videos" style="@if($property->videosYT()->count() == 0 && !$property->video) display: block; @else display: none; @endif ">
                                            <div class="col-md-12" align="center">
                                                    <img src="/images/caja.png" alt="" style ="  height: 180px;  padding-bottom: 25px;">
                                                    <h4>Ooops! No tienes videos</h4>
                                                    <p>Recuerda que un video vale mas que una imagen.</p>
                                                </div>
                                    </div>

                                    @foreach ($property->videosYT() as $video)
                                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 video" id="div-file-{{$video->id}}"
                                        style="margin-bottom:15px;">
                                        
                                            
                                        <button data-id="{{$video->id}}" class="btn btn-danger btn-delete-yt btn-xs"
                                            style="position:absolute; z-index: 1;" value="{{$video->src}}">
                                            <i class="fa fa-times" style="font-size: large;padding: 5px;" aria-hidden="true"></i></button>
                                        <iframe src="{{$video->src}}" frameborder="0"
                                            style="width: -webkit-fill-available; height: -webkit-fill-available;"></iframe>
                                        </div>
                                    
                                    @endforeach

                                    @if ($property->video)
                                    <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 video"
                                        id="div-file-{{$property->video->id}}" style="margin-bottom:15px;">
                                        <button data-id="{{$property->video->id}}"
                                            class="btn btn-danger btn-delete-yt btn-xs"
                                            style="position:absolute; z-index: 1;" value="{{$property->video->src}}">
                                            <i class="fa fa-times" style="font-size: large;padding: 5px;" aria-hidden="true"></i></button>
                                        <video controls style="width: 100%;height:-webkit-fill-available; object-fit: fill;">
                                            <source src="{{$property->video->src}}">
                                        </video>
                                    </div>
                                    @endif

                                </div>

                                {{--  </form>  --}}
                                {{--  </div>  --}}
                            </div>
                        </div>
                        <div class=" col-xs-12 text-center"
                        style="margin-top:35px;">
                        <a role="button" id="btn-cancel" class="btn btn-default" href="{{route('view.property', $property->id)}}">
                            Ir a la propiedad <i class="fa fa-arrow-circle-left" aria-hidden="true"></i>
                        </a>&nbsp;
                        <a role="button" id="btn-save" href="{{route('add.images.properties', $property->id)}}"
                            class="btn btn-primary waves-effect waves-light">
                            Guardar &nbsp; <i class="fa fa-check"></i>
                        </a>
                    </div>
                </div>
                
            </div>

        </div>
    </div>
</div>
</div>
</div>


<div id="add_yt" class="modal modal-edu-general default-popup-PrimaryModal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-close-area modal-close-df">
                <a class="close" data-dismiss="modal" href="#"><i class="fa fa-close"></i></a>
            </div>
            <div class="modal-body">
                <i class="fa fa-youtube-play fa-3x" style="color:#cc0000" aria-hidden="true"></i>
                <h2>Agregue un video de YouTube</h2>
                <h5>(Máximo 5 enlaces)</h5>
                <form action="{{ url('files/upload/store') }}" method="post" id="form-yt">
                    {{ csrf_field() }}
                    <input type="hidden" name="property_id" value="{{$property->id}}">
                    <input type="hidden" name="type" value="3">

                        <input type="text" class="form-control input-youtube" placeholder="Ingresa la url de tu video" name="enlace[]" id="enlace1" style="margin:15px;">

                </form>
            </div>
            <div class="modal-footer">
                <div class="col-md-12 text-center">
                    <a role="button" data-dismiss="modal"  class="btn" style="color: #3077b7;
                    background-color: #fff;
                    border-color:   ;">
                        Cancelar <i class="fa fa-times text-default" aria-hidden="true"></i>
                    </a>&nbsp;
                    <a role="button" data-dismiss="modal" id="btn-save-yt"
                        class="btn btn-primary waves-effect waves-light">
                        Guardar &nbsp; <i class="fa fa-check"></i>
                    </a>
                    
                </div>
               
            </div>
        </div>
    </div>
</div>



@push('scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script src="{{ asset('admin/js/icheck/icheck.min.js') }}"></script>
<script src="{{ asset('admin/js/icheck/icheck-active.js') }}"></script>
<script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
<script src="{{ asset('admin/js/jquery.magnific-popup.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script src="{{ asset('js/multimedia-control.js') }}"></script>
<script >
    const id_property = {{$property->id}};

    const optionsBar = $('#options');
    const images = $('.img');
    
  $('.container-image').click(function(e){
    e.preventDefault();
    $(this).toggleClass('select-image')
    
    if($('.select-image').length){
        optionsBar.stop(true, true).fadeIn();
        if ($('.select-image').length > 1) {
            $('#options-single').fadeOut();
        }else{
            $('#options-single').stop(true, true).fadeIn();
        }
    }else{
        optionsBar.fadeOut();
    }
  });


  $('.btn-open').click(function(){
    
    $('.select-image').first().children('div').children('a').trigger('click');

  });

    @if($errors->any()) 
        @foreach($errors as $error)
        Lobibox.notify('error', {
                    title: 'Error',
                    position: 'top right',
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: "{{$error}}"
                });
        @endforeach
    @endif
        


    var grid = document.getElementById('grid');
   // var header = $('#header_top');

    new Sortable(grid, {
        draggable: '.div-img',
        animation: 150,
        handle: '.handle',
        swap: true,

        onUpdate: function (/**Event*/evt) {
           
            ChangeOrder(evt.oldIndex, evt.newIndex);
           
        },
    //  onStart: function (/**Event*/evt) {
	//	 header.hide();
	//   },
    //      onEnd: function (/**Event*/evt) {
	//	 header.show();
	//   },
       
    });
    

    function ChangeOrder(oldIndex, newIndex){
        console.log(oldIndex, newIndex)
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
               url: '/properties/order-images',
               data: {property_id:id_property, old : oldIndex, new : newIndex},                
               type: 'POST',   
               success: function (res) {
                   
                console.log(res)
               },      
               error: function (res) {
                console.log(res)
               },      
        });
   }

</script>

@endpush
@endsection
