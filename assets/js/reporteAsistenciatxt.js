
Modulo = {
    baseurl: "",
    validator: false,
    datosTabla: []    
    
    , fnObtenerDatos: function (fechaInicio, fechaFin, em) {
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "controllerReporteAsistenciatxt/exportar";
        
        $.ajax({
            url: varURL,
            type: 'post',
            data: {
                fechaInicio: fechaInicio,
                fechaFin: fechaFin,
                empresa_id: parseInt(em),
            },
        }).done(function (data) {
            $(".loadTable").css("display", "none");
 
            var a = document.createElement('a'); 
            var file_name = "ReporteAsistencia_" + $('#planta option:selected').html() + "_" + fechaFin;
            a.href = window.location.origin + "/nomina/reporteAsistenciatxt.txt";
            a.download = file_name;
            a.click();
        }).fail(function() {
            $(".loadTable").css("display", "none");
            alert("Error al descargar el archivo");
        });
    }

    , fnCargarPlantas: function () {
        var varURL = Modulo.baseurl + "MenuController/obtenerPlantas";
        $.ajax({
            url: varURL,
            type: 'get',
            dataType: 'json'
        }).done(function (data) {
            $('#planta').html('');
            $.each(data, function (i, item) {
                $('#planta').append('<option value="' + item.empresa_id + '">' + item.nombre_empresa + '</option>');
            });
        }).fail(function() {
            console.log("Error al cargar plantas");
        });
    }

    , fnInicializar: function () {
        // Inicializar datepickers
        $("#datepicker").datepicker({
            format: "yyyy/mm/dd",
            autoclose: true
        });
        
        $("#datepicker2").datepicker({
            format: "yyyy/mm/dd",
            autoclose: true
        });
        
        // Cargar plantas
        Modulo.fnCargarPlantas();
        
        // Establecer fechas por defecto
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        
        $("#fecha1").val(firstDay.getFullYear() + "/" + 
                        (firstDay.getMonth() + 1).toString().padStart(2, '0') + "/" + 
                        firstDay.getDate().toString().padStart(2, '0'));
        
        $("#fecha2").val(today.getFullYear() + "/" + 
                        (today.getMonth() + 1).toString().padStart(2, '0') + "/" + 
                        today.getDate().toString().padStart(2, '0'));

        // Eventos
        $("#buscar").click(function () {
            var fechaInicio = $("#fecha1").val();
            var fechaFin = $("#fecha2").val();
            var planta = $("#planta").val();
            
            if (!fechaInicio || !fechaFin || !planta) {
                alert("Por favor complete todos los campos");
                return;
            }
            
            Modulo.fnObtenerDatos(fechaInicio, fechaFin, planta);
        });
    }
};

$(document).ready(function () {
    Modulo.fnInicializar();
});
