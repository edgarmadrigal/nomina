
Modulo = {
    baseurl: ""
    //http://192.168.128.24:8080/nomina/
    ,validator: false

    //Válida campos Obligatorios
    
    , fnCargarSelects(){
        var varURL = Modulo.baseurl + "checadasBiostar/consultaPlanta";
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
    }
    //Load
    , Init: function () {
        Modulo.fnCargarSelects();
    }
}
$(document).ready(function () {
    Modulo.Init();
    
    $(document).on('click', '#actualizarTodo', function (e) {
         $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' }); 
        
            var varURL = Modulo.baseurl + "asistencias/actualizarTodo";
            $.ajax({
                url:  varURL,
                type: 'post',
                dataType: 'json'
            }).done(function (data) { 
                $.unblockUI();
               alert('Termino la actualizacion Completa con exito');
            });
    });
    
    $(document).on('click', '#actualizar', function (e) {
        if(!$('#NoSemana').val()){
            alert('Favor de capturar el numero de semana');
        }
        else if($('#planta').val()==0){
            alert('Favor de capturar la planta');
        }else{
            $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' }); 

            var NoSemana=parseInt($("#NoSemana").val());
            var anio=$("#anio").val();
            var planta=parseInt($("#planta").val());

             var varURL = Modulo.baseurl + "asistencias/actualizar";
             $.ajax({
                 url:  varURL,
                 type: 'post',
                 dataType: 'json'
                 ,data: {
                    NoSemana:NoSemana,
                    anio:anio,
                    planta:planta,
                },
             }).done(function (data) { 
                 $.unblockUI();
                alert('Termino la actualizacion con exito');
             });
        }
     });

     
    $(document).on('click', '#borrar', function (e) {
        if(!$('#NoSemana').val()){
            alert('Favor de capturar el numero de semana');
        }
        else if($('#planta').val()==0){
            alert('Favor de capturar la planta');
        }else{
            $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' }); 

            var NoSemana=parseInt($("#NoSemana").val());
            var anio=$("#anio").val();
            var planta=parseInt($("#planta").val());

             var varURL = Modulo.baseurl + "asistencias/borrar";
             $.ajax({
                 url:  varURL,
                 type: 'post',
                 dataType: 'json'
                 ,data: {
                    NoSemana:NoSemana,
                    anio:anio,
                    planta:planta,
                },
             }).done(function (data) {
                 $.unblockUI();
                alert('Termino la actualizacion con exito');
             });
        }
     });
//puesto

$(document).on('click', '#buscarAcumulado', function (e) {
    e.preventDefault();  
    if(!$('#NoSemana').val()){
        alert('Favor de capturar el numero de semana');
    }
    else if($('#planta').val()==0){
        alert('Favor de capturar la planta');
    }
    else{
        var departamento=$("#departamento").val();
        var puesto=$("#puesto").val();
        var noempleado=$("#noempleado").val();

        if($("#departamento").val()==null || $("#departamento").val()==0 ){
            $("#departamento").val('');
            var departamento='';
        }
        if($("#puesto").val()==null  || $("#puesto").val()=='null' || $("#puesto").val()==0   ){
            $("#puesto").val('');
            var puesto='';
        }
        if($("#noempleado").val()==null){
            $("#noempleado").val('');
            var noempleado='';
        }

        var varURL = Modulo.baseurl 
        + "asistencias/reporteAsistenciaNominas?NoSemana="
        +$("#NoSemana").val()+'&anio='+$("#anio").val()+'&planta='+parseInt($("#planta").val())+'&departamento='+departamento+'&puesto='+puesto+'&noempleado='+noempleado; 
        
    /*
    //para debugear
    $.ajax({
        url:  varURL,
        type: 'get',
        dataType: 'json'
    }).done(function (data) {
        alert(data);
        //perfiles= JSON.parse(JSON.stringify());       
    });
    
        */
        
        window.open(varURL);
    }
   // window.location.assign(varURL);
});






    $(document).on('click', '#buscar', function (e) {
        e.preventDefault();  
        if(!$('#NoSemana').val()){
            alert('Favor de capturar el numero de semana');
        }
        else if($('#planta').val()==0){
            alert('Favor de capturar la planta');
        }
        else{
            var departamento=$("#departamento").val();
            var puesto=$("#puesto").val();
            var noempleado=$("#noempleado").val();

            if($("#departamento").val()==null || $("#departamento").val()==0 ){
                $("#departamento").val('');
                var departamento='';
            }
            if($("#puesto").val()==null  || $("#puesto").val()=='null' || $("#puesto").val()==0   ){
                $("#puesto").val('');
                var puesto='';
            }
            if($("#noempleado").val()==null){
                $("#noempleado").val('');
                var noempleado='';
            }

            var varURL = Modulo.baseurl 
            + "asistencias/reporteAsistencia?NoSemana="
            +$("#NoSemana").val()+'&anio='+$("#anio").val()+'&planta='+parseInt($("#planta").val())+'&departamento='+departamento+'&puesto='+puesto+'&noempleado='+noempleado; 
            
        /*
        //para debugear
        $.ajax({
            url:  varURL,
            type: 'get',
            dataType: 'json'
        }).done(function (data) {
            alert(data);
            //perfiles= JSON.parse(JSON.stringify());       
        });
        
            */
            
            window.open(varURL);
        }
       // window.location.assign(varURL);
    });

    /*
    $('#planta').on('change', function() {
        $('#departamento').empty();   
        $('#puesto').empty();
        var planta=parseInt($('#planta').val());                
        var varURL = Modulo.baseurl + "checadasBiostar/consultaDepartamento";
        $.ajax({
            url:  varURL,
            data: {
                planta:planta,
            },
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));                     
            $('#departamento').empty();                   
            $('#puesto').empty();  
            $('#departamento').append('<option value="0">Selecciona un departamento</option>');
            $.each(perfiles, function( index, value ) { 
                $('#departamento').append('<option value="'+value.ID+'">'+value.NOMBRE+'</option>');
            });
        });
    });

    $('#departamento').on('change', function() {
        var planta=parseInt($('#planta').val());                       
        var varURL = Modulo.baseurl + "checadasBiostar/consultaPuesto";
        $.ajax({
            url:  varURL,
            data: {
                planta:planta
            },
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));                  
            $('#puesto').empty();  
            $('#puesto').append('<option value="0">Selecciona una puesto</option>');
            $.each(perfiles, function( index, value ) { 
                $('#puesto').append('<option value="'+value.ID+'">'+value.descripcion+'</option>');
            });
        });                
    });*/


});

















account_data_emailaddress_DWT115
account_data_emailaddress_DWT116
account_data_emailaddress_DWT117
account_data_emailaddress_DWT118
account_data_emailaddress_DWT119
account_data_emailaddress_DWT120
account_data_emailaddress_DWT121
account_data_emailaddress_DWT122
account_data_emailaddress_DWT123
account_data_emailaddress_DWT124
account_data_emailaddress_DWT125
account_data_emailaddress_DWT126
account_data_emailaddress_DWT127
account_data_emailaddress_DWT128
account_data_emailaddress_DWT129
account_data_emailaddress_DWT130
account_data_emailaddress_DWT131
account_data_emailaddress_DWT132
account_data_emailaddress_DWT133
account_data_emailaddress_DWT134
account_data_emailaddress_DWT135
account_data_emailaddress_DWT136
account_data_emailaddress_DWT137
account_data_emailaddress_DWT138
account_data_emailaddress_DWT139
account_data_emailaddress_DWT140
account_data_emailaddress_DWT141
account_data_emailaddress_DWT142
account_data_emailaddress_DWT143
account_data_emailaddress_DWT144



document.querySelectorAll("#account_data_emailaddress_DWT115")
document.querySelectorAll("#account_data_emailaddress_DWT116")
document.querySelectorAll("#account_data_emailaddress_DWT117")
document.querySelectorAll("#account_data_emailaddress_DWT118")
document.querySelectorAll("#account_data_emailaddress_DWT119")
document.querySelectorAll("#account_data_emailaddress_DWT120")
document.querySelectorAll("#account_data_emailaddress_DWT121")
document.querySelectorAll("#account_data_emailaddress_DWT122")
document.querySelectorAll("#account_data_emailaddress_DWT123")
document.querySelectorAll("#account_data_emailaddress_DWT124")
document.querySelectorAll("#account_data_emailaddress_DWT125")
document.querySelectorAll("#account_data_emailaddress_DWT126")
document.querySelectorAll("#account_data_emailaddress_DWT127")
document.querySelectorAll("#account_data_emailaddress_DWT128")
document.querySelectorAll("#account_data_emailaddress_DWT129")
document.querySelectorAll("#account_data_emailaddress_DWT130")
document.querySelectorAll("#account_data_emailaddress_DWT131")
document.querySelectorAll("#account_data_emailaddress_DWT132")
document.querySelectorAll("#account_data_emailaddress_DWT133")
document.querySelectorAll("#account_data_emailaddress_DWT134")
document.querySelectorAll("#account_data_emailaddress_DWT135")
document.querySelectorAll("#account_data_emailaddress_DWT136")
document.querySelectorAll("#account_data_emailaddress_DWT137")
document.querySelectorAll("#account_data_emailaddress_DWT138")
document.querySelectorAll("#account_data_emailaddress_DWT139")
document.querySelectorAll("#account_data_emailaddress_DWT140")
document.querySelectorAll("#account_data_emailaddress_DWT141")
document.querySelectorAll("#account_data_emailaddress_DWT142")
document.querySelectorAll("#account_data_emailaddress_DWT143")
document.querySelectorAll("#account_data_emailaddress_DWT144")
