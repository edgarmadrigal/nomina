
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
    });


});