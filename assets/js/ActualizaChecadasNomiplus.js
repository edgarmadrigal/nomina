  
Modulo = {
    baseurl: "",
    //http://192.168.128.24:8080/nomina/
    validator: false,
    datosTabla: []    
    //Obtiene los datos
    , fnObtenerDatos: function (fechaInicio,fechaFin) {
    $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
        var varURL = Modulo.baseurl + "ActualizaChecadasNomiPlusController/consulta";
        /**Ajax */
        $.ajax({
            url: varURL,
            type: 'post',
            dataType: 'json',
            data: {
                fechaInicio: fechaInicio,
                fechaFin:fechaFin,
            },
            //contentType: 'application/json; charset=utf-8',
        }).done(function (data) {
            alert("Se actualizo con exito ");
            $.unblockUI();

        });
    }
    
    //Load
    , Init: function () {
        
    }
}
var buttonCommon = {
    exportOptions: {
        format: {
            body: function ( data, row, column, node ) {
                return data;
            }
        }
    }
};  
    var table;
    var id_edit;
    $.extend(jQuery.fn.dataTableExt.oSort, {
        "date-uk-pre": function (a) {
            var x;
            try {
                var dateA = a.replace(/ /g, '').split("/");
                var day = parseInt(dateA[0], 10);
                var month = parseInt(dateA[1], 10);
                var year = parseInt(dateA[2], 10);
                var date = new Date(year, month - 1, day)
                x = date.getTime();
            }
            catch (err) {
                x = new Date().getTime();
            }

            return x;
        },

        "date-uk-asc": function (a, b) {
            return a - b;
        },

        "date-uk-desc": function (a, b) {
            return b - a;
        }
    });
    
    $(document).ready(function () {
        //Load
        table = $('#example').DataTable();
        Modulo.Init();
        $("#example").dataTable().fnDestroy();
        base_url="";
        //http://127.0.0.1:8080/nomina/
        TablaVacia = "No se encontraron resultados";
        empleadosTabla=[];
            $('#id').val(0);
        
        
        $(document).on('click', '#buscar', function (e) {
            var fechaInicio=$('#fecha1').val();
            var fechaFin=$('#fecha2').val();
            Modulo.fnObtenerDatos(fechaInicio,fechaFin);
        });
        


    });