
<div id="add_avatar" class="modal modal-edu-general default-popup-PrimaryModal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-close-area modal-close-df">
                <a class="close" data-dismiss="modal" href="#"><i class="fa fa-close"></i></a>
            </div>
            <div class="modal-body text-center">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <h4 style="color:#999;">Avatar</h4>
                    <form enctype="multipart/form-data" id="formcover" method="POST">
                        <label id="cover" for="cover_input" class="container-image">
                            <img src="{{$data->foto_avatar}}" alt="Avatar" id="avatar" class="image">
                            <div class="overlay">
                                <div class="text"><i class="fa fa-upload"></i>
                                    <p style="font-size: large;">Subir imagen</p>
                                    <input class="imagen" style="position:absolute; opacity:0;"
                                        type="file" accept="image/*" name="file" id="cover_input">
                                </div>
                            </div>
                        </label>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <div class="col-md-12 text-center">
                    <button data-dismiss="modal" class="btn btn-success btn-lg" id="btn-save-avatar">
                            Aceptar</button>
                    <button data-dismiss="modal" class="btn btn-danger btn-lg">Cancelar</button>
                </div>
               
            </div>
        </div>
    </div>
</div>