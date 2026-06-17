$(document).ready(function () {
    $(document).on('click', '#subir', function (e) {
        $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
    $("#tablaEmp").css("display", "none");
    $(".loadTable").css("display", "block");  
        var data = new FormData(jQuery('form')[0]);
        jQuery.ajax({
            url: 'importarAnviz/uploadData',
            data: data,
            method: 'POST',           
            cache: false,
            contentType: false,
            processData: false,
            type: 'POST', // For jQuery < 1.9
            success: 
            function(data){
                    alert(data);
                    $.unblockUI();
                    $("#tablaEmp").css("display", "block");
                    $(".loadTable").css("display", "none");                
            },error: function(XMLHttpRequest, textStatus, errorThrown) { 
                //                alert("Status: " + );                 
                alert('Revisa bien el formato deseado '+"Error: " + errorThrown +' '+textStatus);
                $.unblockUI();
                $("#tablaEmp").css("display", "block");
                $(".loadTable").css("display", "none");
            }  
        });
    });
});



