function uploadFile(){    
    var form = new FormData($('#movieFormData')[0]);  
    // Make the ajax call
    $.ajax({
        url: '',
        data: form,                
        cache: false,
        contentType: false,
        processData: false,
        type: 'POST',   
        success: function (res) {
           // your code after succes
        },      
    });  
  
  }