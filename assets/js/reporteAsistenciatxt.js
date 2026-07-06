
Modulo = {
    baseurl: "",
    validator: false,
    datosTabla: []    
    
    , fnObtenerDatos: function (NoSemana, anio, planta, Code, puesto, NoEmpleado) {
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "controllerReporteAsistenciatxt/exportar";
        
        $.ajax({
            url: varURL,
            type: 'post',
            data: {
                NoSemana: parseInt(NoSemana),
                anio: anio,
                planta: planta,
                Code: Code,
                puesto: puesto,
                NoEmpleado: NoEmpleado,
            },
        }).done(function (data) {
            $(".loadTable").css("display", "none");
 
            var blob = new Blob([data], { type: "text/plain" });
            var a = document.createElement('a');
            var nombrePlanta = $('#planta option:selected').html().trim();
            var file_name = "ReporteAsistencia_" + nombrePlanta + "_Semana_" + NoSemana + "_" + anio + ".txt";
            a.href = window.URL.createObjectURL(blob);
            a.download = file_name;
            a.click();
            setTimeout(function() { window.URL.revokeObjectURL(a.href); }, 100);
        }).fail(function() {
            $(".loadTable").css("display", "none");
            alert("Error al descargar el archivo");
        });
    }

    , fnCargarPlantas: function () {
        var varURL = Modulo.baseurl + "checadasBiostar/consultaPlanta";
        $.ajax({
            url: varURL,
            type: 'post',
            dataType: 'json'
        }).done(function (data) {
            $('#planta').html('');
            $('#planta').append('<option value="">Selecciona una planta</option>');
            $.each(data, function (i, item) {
                $('#planta').append('<option value="' + item.id + '">' + item.nombre + '</option>');
            });
        }).fail(function() {
            console.log("Error al cargar plantas");
        });
    }

    , fnInicializar: function () {
        // Cargar plantas
        Modulo.fnCargarPlantas();

        // Eventos
        $("#buscar").click(function () {
            var NoSemana = $("#NoSemana").val();
            var anio = $("#anio").val();
            var planta = $("#planta").val();
            var Code = $("#departamento").val();
            var puesto = $("#puesto").val();
            var NoEmpleado = $("#noempleado").val();

            if (!NoSemana) {
                alert("Favor de capturar el numero de semana");
                return;
            }
            if (!planta) {
                alert("Favor de capturar la planta");
                return;
            }

            Modulo.fnObtenerDatos(NoSemana, anio, planta, Code, puesto, NoEmpleado);
        });
    }
};

$(document).ready(function () {
    Modulo.fnInicializar();
});
