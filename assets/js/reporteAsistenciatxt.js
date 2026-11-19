
Modulo = {
    baseurl: "",
    validator: false,
    datosTabla: []    
    
    , fnObtenerDatos: function (NoSemana, anio, planta, Code, puesto, NoEmpleado, tiempoExtra) {
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
                tiempoExtra: tiempoExtra
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
    , fnDiasTiempoExtra: function () {
        // "1" = si paga tiempo extra ese dia, "0" = no. Orden: L M Mi J V S D
        if (!$("#checkTiempoExtra").is(":checked")) {
            return "0000000";
        }
        var dias = "";
        $(".diaTE").each(function () {
            dias += $(this).is(":checked") ? "1" : "0";
        });
        return dias;
    }
    // Versión del formato de Microsip (assets/formatos/Excepciones Nomina SISAI.txt). Si el formato
    // vuelve a cambiar, se sube este número y el aviso sale otra vez a todos.
    , claveFormato: "txt.formatoMicrosip.v2"
    , fnFormatoConfirmado: function () {
        try { return window.localStorage.getItem(Modulo.claveFormato) === "1"; } catch (e) { return false; }
    }
    , fnFormatoConfirmar: function () {
        try { window.localStorage.setItem(Modulo.claveFormato, "1"); } catch (e) { }
    }
    , fnInicializar: function () {
        // Cargar plantas
        Modulo.fnCargarPlantas();
                // Mostrar/ocultar los dias segun el checkbox general
        $("#checkTiempoExtra").on("change", function () {
            $("#filaDiasTE").toggle($(this).is(":checked"));
        });

        // Eventos
        $("#descargar").click(function () {
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

            var tiempoExtra = $("#checkTiempoExtra").is(":checked");
            var descargar = function () {
                Modulo.fnObtenerDatos(NoSemana, anio, planta, Code, puesto, NoEmpleado, tiempoExtra);
            };
            // AJUSTE 2026-11-19: el TXT trae DIAS_VACACIONES y TIPO_MOVIMIENTO; se avisa del formato
            // nuevo de Microsip hasta que el usuario confirma (una vez por navegador).
            if (Modulo.fnFormatoConfirmado()) {
                descargar();
                return;
            }
            $("#cbFormatoNuevo").prop("checked", false);
            $("#btnDescargarTxtOk").prop("disabled", true).off("click").on("click", function () {
                Modulo.fnFormatoConfirmar();
                $("#modalFormatoTxt").modal("hide");
                descargar();
            });
            $("#modalFormatoTxt").modal("show");
        });

        $("#cbFormatoNuevo").on("change", function () {
            $("#btnDescargarTxtOk").prop("disabled", !$(this).is(":checked"));
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
                        var diasTiempoExtra = Modulo.fnDiasTiempoExtra();
            var tiempoExtra = diasTiempoExtra.indexOf("1") >= 0 ? 1 : 0;


             var varURL = Modulo.baseurl + "controllerReporteAsistenciatxt/actualizar";
             $.ajax({
                 url:  varURL,
                 type: 'post',
                 dataType: 'json'
                 ,data: {
                    NoSemana:NoSemana,
                    anio:anio,
                    planta:planta,
                    tiempoExtra:tiempoExtra,
                    diasTiempoExtra: diasTiempoExtra
                },
             }).done(function (data) { 
                 $.unblockUI();
                if (data && data.sincronizoMicrosip === false) {
                    alert('Termino la actualizacion, pero NO se pudieron traer las vacaciones e incapacidades de Microsip en este momento.\n\nSe uso lo que ya estaba en el sistema; si capturaron vacaciones en Microsip en la ultima hora, vuelve a procesar mas tarde.');
                } else {
                    alert('Termino la actualizacion con exito');
                }
             }).fail(function () {
                 $.unblockUI();
                 alert('Error al procesar la semana');
             });
        }
     });
    }
};

$(document).ready(function () {
    
    Modulo.fnInicializar();
});
