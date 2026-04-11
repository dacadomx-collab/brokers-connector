$(document).ready(function () {


    $("#btn-submit").click(function () {
        
        $( "#form-control" ).validate({
            rules: {
              password: "required",
              password_confirmation: {
                equalTo: "#password"
              }
            }
          });

        if (!$("#form-control").valid()) {
            return
        }
        
       


        $("#form-control").submit();
    });

})
