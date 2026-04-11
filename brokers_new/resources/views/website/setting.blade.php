@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/notifications/Lobibox.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/notifications/notifications.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/form/all-type-forms.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/nano.min.css"/> 

<style>
    /*
    .logo {
        z-index: 1;
        background-image: url('{{ $company->logo  }}');
    }

    .cover {
        z-index: 1;
        background-image: url('{{ $company->cover  }}');
    }

    .banner {
        z-index: 1;
        background-image: url('{{ $company->banner  }}');
    }

    .team {
        z-index: 1;
        background-image: url('{{ $company->team  }}');
    }
*/
    /*
          .imga:hover, .imga:focus, .imga:active {
            display: block; 
            color: white;
            background-image: rgba(0,0,0,1) !important; 
            z-index: 2; 
            position: fixed; 
            
        }

    

        .imga{
            display: inline-block;
            vertical-align: middle;
            -webkit-transform: perspective(1px) translateZ(9999);
            transform: perspective(1px) translateZ(9999);
            box-shadow: 0 0 1px rgba(0, 0, 0, 0);
            overflow: hidden;
            -webkit-transition-duration: 0.3s;
            transition-duration: 0.3s;

            background-color: #026cf0a8;
            background-repeat: no-repeat;
            background-size: cover; 
        }
        */


    /*
        .hvr-fade {
            display: inline-block;
            vertical-align: middle;
            -webkit-transform: perspective(1px) translateZ(0);
            transform: perspective(1px) translateZ(0);
            box-shadow: 0 0 1px rgba(0, 0, 0, 0);
            overflow: hidden;
            -webkit-transition-duration: 0.3s;
            transition-duration: 0.3s;
            -webkit-transition-property: color, background-color;
            transition-property: color, background-color;
        }
    */

.container-image {
  position: relative;
  width: 100%;
  height: 250px;
  /*box-shadow: 2px 9px 20px;*/
  cursor: pointer;
  margin-bottom: 25px;
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
  opacity: 0;
  transition: .3s ease;
  background-color: rgba(0, 0, 0, .5);

}

.container-image:hover .overlay {
  opacity: 1;
}

.text {
  color: white;
  font-size: -webkit-xxx-large;
  position: absolute;
  top: 50%;
  left: 50%;
  -webkit-transform: translate(-50%, -50%);
  -ms-transform: translate(-50%, -50%);
  transform: translate(-50%, -50%);
  text-align: center;
}

</style>

@endpush

@section('title', 'Mi sitio web')

@section('breadcome')
<li><a href="{{ url('/') }}">Inicio</a> <span class="bread-slash">/</span>
</li>
<li><span class="bread-blod">Mi sitio web</span>
</li>
@endsection

@section('content')
<!-- Single pro tab review Start-->
<div class="single-pro-review-area mt-t-30 mg-b-15">
    <div class="container-fluid">

        <div class="row">
           
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <div class="product-payment-inner-st">

                        <ul id="myTabedu1" class="tab-review-design">
                        <li class="active"><a href="#information">Información</a></li>
                        <li class=""><a href="#images"> Imágenes</a></li>
                        
                    </ul>
                    <div id="myTabContent" class="tab-content custom-product-edit">
                        <div class="product-tab-list tab-pane fade active in" id="information">
                            <div class="row">
                                    <div class="courses-area">
                                      @if ($company->dominio)
                                          
                                      <h3 class="text-center"><a target="_blank" href="http://{{$company->dominio}}"> www.{{$company->dominio}}</a></h3>
                                      <hr style="border-style: dashed;">
                                          
                                      @endif
                                        <div class="container-fluid analysis-progrebar-ctn">
                                                <form action="{{route('website.update')}}" method="post" id="form-control">
                                                   <input type="hidden" name="id" value="{{Auth::user()->company->id}}">
                                                   <input id="main_color" type="hidden" value="{{$data ? $data->main_color : '#FF9800'}}" name="main_color">
                                                   <input id="title_color" type="hidden" value="{{$data ? $data->title_color : '#000'}}" name="title_color">
                                                    @csrf
                                                    
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label >Título del sitio web <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title='Este nombre se mostrara en su sitio web, si se deja vacío se mostrara el nombre de la compañia'></i></label>
                                                            <input type="text" class="form-control" maxlength="255"  name="name" placeholder="Título del sitio web"
                                                            value="{{$data ? $data->name : ''}}">
                                                        </div>
                                                        
                                                        <div class="form-group">
                                                            <label >Palabras clave <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title='Se utiliza para resumir el contenido de un documento en base a unas cuantas palabras clave'></i></label>
                                                            <input type="text" class="form-control" maxlength="255"  name="keywords" placeholder="Palabras clave separadas por coma"
                                                            value="{{isset($data->keywords) ? $data->keywords : ''}}">
                                                        </div>
                                                        
                                                        <div class="form-group edit-ta-resize">
                                                            <label for="">¿Quiénes Somos? <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title='Este texto será visualizado en la seccion "Acerca de nosotros" en tu pagina web'></i></label> 
                                                            <textarea name="about" rows="4" style="height:unset;" placeholder="Escribe acerca de tu organización">{{$company->about}}</textarea>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">

                                                        {{--  <div class="col-md-12">
                                                            <label for="">Seleccione un template</label>
                                                            <div class="">

                                                                <input type="radio" name="" id="">
                                                                <img style="max-width: 150px;" src="/images/tempsnip.jpg" alt="">
                                                            </div>
                                                            <hr>
                                                        </div>  --}}
                                                        <br>
                                                        
                                                        <div class="col-md-4 col-xs-6 text-center">
                                                            <label for="">Color Principal <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title='Este color será el principal para su sitio web, asegurate de hacer una buena elección!'></i></label>
                                                            <div id="main" class="color-picker">
                                                                <button type="button" class="pcr-button" role="button" aria-label="toggle color picker dialog" ></button>
                                                              </div>
                                                        </div>
                                                        <div class="col-md-4 col-xs-6 text-center">
                                                            <label for="">Color Titulos <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title='Este color será el que lleven los titulos de tu sitio web'></i></label>
                                                            <div id="title" class="color-picker">
                                                                <button type="button" class="pcr-button" role="button" aria-label="toggle color picker dialog" ></button>
                                                              </div>
                                                        </div>
                                                    
                                                
                                                    </div>
                                                    
                    
                                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center" style="margin-top:35px;">
                           
                                                            <button type="button" id="btn-cancel" 
                                                                class="btn btn-default">Cancelar &nbsp;<i
                                                                    class="fa fa-times"></i></button>&nbsp;
                                                            <button type="button" id="btn-save" 
                                                                class="btn btn-primary waves-effect waves-light">Guardar &nbsp;<i
                                                                    class="fa fa-check"></i></button>
                                                        
                                                    </div>
                                                </form>     
                                            </div>
                                        </div>
                            </div>
                        </div>
                        <div class="product-tab-list tab-pane fade" id="images">
                            <div class="container-fluid analysis-progrebar-ctn">
                            <div class="row text-center">

                                    <div class="col-md-3 col-sm-6">
                                        <h4 style="color:#999;">Logo</h4>
                                        <form enctype="multipart/form-data" id="formlogo" method="POST">
                                            <label id="logo" for="logo_input" class="container-image">
                                                <img src="{{ $company->logo_image  }}" alt="Avatar" class="image">
                                                <div class="overlay">
                                                    <div class="text"><i class="fa fa-upload"></i>
                                                        <p style="font-size: large;">Subir imagen</p>
                                                        <input class="imagen" style="position:absolute; opacity:0;"
                                                            type="file" accept="image/*" name="logo" id="logo_input">
                                                    </div>
                                                </div>
                                            </label>
                                        </form>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <h4 style="color:#999;">Portada</h4>
                                        <form enctype="multipart/form-data" id="formcover" method="POST">
                                            <label id="cover" for="cover_input" class="container-image">
                                                <img src="{{ $company->cover_image  }}" alt="Avatar" class="image">
                                                <div class="overlay">
                                                    <div class="text"><i class="fa fa-upload"></i>
                                                        <p style="font-size: large;">Subir imagen</p>
                                                        <input class="imagen" style="position:absolute; opacity:0;"
                                                            type="file" accept="image/*" name="cover" id="cover_input">
                                                    </div>
                                                </div>
                                            </label>
                                        </form>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <h4 style="color:#999;">Banner</h4>
                                        <form enctype="multipart/form-data" id="formbanner" method="POST">
                                            <label id="banner" for="banner_input" class="container-image">
                                                <img src="{{ $company->banner_image  }}" alt="Avatar" class="image">
                                                <div class="overlay">
                                                        <div class="text"><i class="fa fa-upload"></i>
                                                            <p style="font-size: large;">Subir imagen</p>
                                                        <input class="imagen" style="position:absolute; opacity:0;"
                                                            type="file" accept="image/*" name="banner" id="banner_input">
                                                    </div>
                                                </div>
                                            </label>
                                        </form>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <h4 style="color:#999;">Equipo</h4>
                                        <form enctype="multipart/form-data" id="formteam" method="POST">
                                            <label id="team" for="team_input" class="container-image">
                                                <img src="{{ $company->team_image  }}" alt="" class="image">
                                                <div class="overlay">
                                                        <div class="text"><i class="fa fa-upload"></i>
                                                            <p style="font-size: large;">Subir imagen</p>
                                                        <input class="imagen" style="position:absolute; opacity:0;"
                                                            type="file" accept="image/*" name="team" id="team_input">
                                                    </div>
                                                </div>
                                            </label>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>


   
    <script>
         $('.phone-input').keypress( function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {

        return false;
        }
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).ready(function (e) {
        $('.imagen').change(function () {
    
            const image = $(this).parents('label').children('img');
            const form_id = $(this).parents('form').attr('id');
            const formData = new FormData($('#' + form_id)[0]);
         
           if($(this).val()){
            $.ajax({
                url: '/files/company/store',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: "html",
                cache: true,
                success: function (res) {
                    const response = JSON.parse(res);
                    image.attr('src',  response.image + '?t='+ new Date().getTime());  
                    //console.log(res,"--->SUCCESS<--------");
                    //console.log(res.image);

                },
                beforeSend: function (res) {
                    image.attr('src','/img/Loading_icon.gif');
                },
                error: function (xhr, status, response) {

                    var error = jQuery.parseJSON(xhr.responseText);
                    var info = error.msg;
                    image.attr('src', '/img/uploadfailed.jpg')
                   
                    Lobibox.notify('error', {
                        title: 'Error',
                        position: 'top right',
                        showClass: 'fadeInDown',
                        hideClass: 'fadeUpDown',
                        msg: info
                    });
                    //console.log(res,"--->SUCCESS<--------");
                    //console.log(res.image);
                }, 
            });
           }
        });
    });

</script>
@endpush

@push('scripts')
    <script src="{{ asset('admin/js/notifications/Lobibox.js') }}"></script>
    <script src="{{ asset('js/jquery.validate.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>

    @if (Session::has('success'))
        <script>
            Lobibox.notify('success', {
                title: 'Información actualizada',
                position: 'top right',
                showClass: 'fadeInDown',
                hideClass: 'fadeUpDown',
                msg: "{{ session('success') }}"
            });
        </script>
    @endif

    @if ($errors->any())
    <script>
            @foreach ($errors->all() as $error)
                Lobibox.notify('error', {
                    title: 'Error',
                    position: 'top right',
                    showClass: 'fadeInDown',
                    hideClass: 'fadeUpDown',
                    msg: "{{ $error }}"
                });
            @endforeach
    </script>
    @endif

    <script>

document.getElementById('btn-cancel')
        .addEventListener('click', function(){
            window.location.href = '/home';
        })

        $("#btn-save").click(function(){
            if (!$("#form-control").valid()) 
                return

            $("#form-control").submit();
        });
        const title = Pickr.create({
            el: '#title',
            theme: 'nano', // or 'monolith', or 'nano'
           // useAsButton: true,
           default: "{{$data ? $data->title_color : '#555'}}",
            swatches: [
                'rgba(244, 67, 54, 1)',
                'rgba(233, 30, 99, 0.95)',
                'rgba(156, 39, 176, 0.9)',
                'rgba(103, 58, 183, 0.85)',
                'rgba(63, 81, 181, 0.8)',
                'rgba(33, 150, 243, 0.75)',
                'rgba(3, 169, 244, 0.7)',
                'rgba(0, 188, 212, 0.7)',
                'rgba(0, 150, 136, 0.75)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(139, 195, 74, 0.85)',
                'rgba(205, 220, 57, 0.9)',
                'rgba(255, 235, 59, 0.95)',
                'rgba(255, 193, 7, 1)'
            ],
            
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    save: true,
                },
            },
            strings: {
                save: 'Guardar',  // Default for save button
             }
        });
        const main = Pickr.create({
            el: '#main',
            theme: 'nano', // or 'monolith', or 'nano'
           // useAsButton: true,
           default: "{{$data ? $data->main_color : '#FF9800'}}",
            swatches: [
                'rgba(244, 67, 54, 1)',
                'rgba(233, 30, 99, 0.95)',
                'rgba(156, 39, 176, 0.9)',
                'rgba(103, 58, 183, 0.85)',
                'rgba(63, 81, 181, 0.8)',
                'rgba(33, 150, 243, 0.75)',
                'rgba(3, 169, 244, 0.7)',
                'rgba(0, 188, 212, 0.7)',
                'rgba(0, 150, 136, 0.75)',
                'rgba(76, 175, 80, 0.8)',
                'rgba(139, 195, 74, 0.85)',
                'rgba(205, 220, 57, 0.9)',
                'rgba(255, 235, 59, 0.95)',
                'rgba(255, 193, 7, 1)'
            ],
            
            components: {
                preview: true,
                opacity: true,
                hue: true,
                interaction: {
                    save: true,
                },
            },
            strings: {
                save: 'Guardar',  // Default for save button
             }
        });

        main.on('save', function(e){
           let color = e.toHEXA().toString();
            $('#main_color').val(color);
            main.hide();
        })
        title.on('save', function(e){
            let color = e.toHEXA().toString();
            $('#title_color').val(color);
            title.hide();
        })
        

    </script>
@endpush