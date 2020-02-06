
Modulo = {
    baseurl: ""
    //http://192.168.128.24:8080/nomina/
    ,validator: false

    //Válida campos Obligatorios
    ///2016
    
    , fnCargarSelects(){
        var varURL = Modulo.baseurl + "contrato/consultaSalario";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#salario').append('<option value="">Selecciona un salario</option>');
            $.each(perfiles, function( index, value ) { 
                $('#salario').append('<option value="'+value.id+'">'+value.salario+' - '+value.año+'</option>');
              });
        });

        var varURL = Modulo.baseurl + "contrato/consultaRepresentante";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#representante').append('<option value="">Selecciona un representante</option>');
            $.each(perfiles, function( index, value ) { 
                $('#representante').append('<option value="'+value.id+'">'+value.nombre+'</option>');
              });
        });

        var varURL = Modulo.baseurl + "contrato/ConsultaHorarioContrato";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#horario').append('<option value="">Selecciona un horario</option>');
            $.each(perfiles, function( index, value ) { 
                $('#horario').append('<option value="'+value.id+'">'+value.nombre+'</option>');
              });
        });
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
        var varURL = Modulo.baseurl + "contrato/ConsultaDescanso";
        $.ajax({
            url:  varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {                
            perfiles= JSON.parse(JSON.stringify(data));
            $('#descanso').append('<option value="">Selecciona un descanso</option>');
            $.each(perfiles, function( index, value ) { 
                $('#descanso').append('<option value="'+value.id+'">'+value.nombre+'</option>');
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
    

    $(document).on('click', '#buscar', function (e) {
        e.preventDefault();  
        if($('#salario').val()==0 || $('#representante').val()==0 || $('#horario').val()==0 || $('#planta').val()==0 || $('#descanso').val()==0 || $('#noempleado').val()=='' ){
            alert('Favor de capturar toda la informacion requerida');
        }        
        else{
            var varURL = Modulo.baseurl 
            + "contrato/ReporteContrato?idsalario="+parseInt($("#salario").val())
            +'&idrepresentante='+parseInt($("#representante").val())
            +'&idhorario='+parseInt($("#horario").val())
            +'&idempleado='+parseInt($("#noempleado").val())
            +'&idempresa='+parseInt($("#planta").val())
            +'&iddescanso='+parseInt($("#descanso").val()); 
            
            
            window.open(varURL);
        }
    });

    

});