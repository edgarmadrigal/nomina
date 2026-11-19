$(document).ready(function () {

       
        var varURL = "checadasBiostar/consultaPlanta";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#planta').append('<option value="">Selecciona una planta</option>');
            $.each(perfiles, function( index, value ) { 
                $('#planta').append('<option value="'+value.id+'">'+value.nombre+'</option>');
                });
        });

    $(document).on('click', '#subir', function (e) {

        if($('#planta').val()=='' || $('#planta').val()==null){
            alert('Selecciona una planta');
            return false;
        }else{
            $.ajax({
                url: 'importar/subirPlanta',
                type: 'post',
                dataType: 'json',
                data: {
                    'planta'                : $('#planta').val()
                }
            }).done(function (data) {
                console.log(data);
            });       
            $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
            $("#tablaEmp").css("display", "none");
            $(".loadTable").css("display", "block");  
                var data = new FormData(jQuery('form')[0]);
                jQuery.ajax({
                    url: 'importar/uploadData',
                    data: data,
                    method: 'POST',           
                    cache: false,
                    contentType: false,
                    processData: false,
                    type: 'POST', // For jQuery < 1.9
                    success: 
                    function(data){
                            $('#resultadoImport').text(data).show();
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
        }
    });
});



