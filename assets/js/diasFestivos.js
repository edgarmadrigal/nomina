Modulo = {
    baseurl: "",
    festivos: [],

    fnCargarAnios: function () {
        var anioActual = new Date().getFullYear();
        var $select = $('#filtroAnio');
        $select.html('');
        for (var y = anioActual - 2; y <= anioActual + 3; y++) {
            $select.append('<option value="' + y + '">' + y + '</option>');
        }
        $select.val(anioActual);
    },

    fnCargarFestivos: function (anio) {
        $(".loadTable").css("display", "block");
        var varURL = Modulo.baseurl + "controllerDiasFestivos/listar";
        $.ajax({
            url: varURL,
            type: 'post',
            data: { anio: anio },
            dataType: 'json'
        }).done(function (data) {
            $(".loadTable").css("display", "none");
            Modulo.festivos = data;
            Modulo.fnPintarTabla(data);
        }).fail(function () {
            $(".loadTable").css("display", "none");
            alert('Error al cargar los días festivos');
        });
    },

    fnPintarTabla: function (data) {
        var dias = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        var html = '';
        $.each(data, function (i, item) {
            // 'T00:00:00' fuerza a que el navegador interprete la fecha
            // en hora local en vez de UTC -- si no, en algunos husos
            // horarios el día de la semana calculado sale corrido.
            var fechaObj = new Date(item.fecha + 'T00:00:00');
            var diaSemana = dias[fechaObj.getDay()];
            html += '<tr>';
            html += '<td>' + item.fecha + '</td>';
            html += '<td>' + diaSemana + '</td>';
            html += '<td>' + item.descripcion + '</td>';
            html += '<td>' + item.anio + '</td>';
            html += '<td>';
            html += '<button class="btn btn-sm btn-warning btnEditar" data-id="' + item.id + '" title="Editar"><i class="fa fa-edit"></i></button> ';
            html += '<button class="btn btn-sm btn-danger btnEliminar" data-id="' + item.id + '" title="Eliminar"><i class="fa fa-trash"></i></button>';
            html += '</td>';
            html += '</tr>';
        });
        if (html === '') {
            html = '<tr><td colspan="5" class="text-center">No hay días festivos registrados para este filtro.</td></tr>';
        }
        $('#cuerpoTablaFestivos').html(html);
    },

    fnAbrirModalNuevo: function () {
        $('#festivoId').val('');
        $('#festivoFecha').val('');
        $('#festivoDescripcion').val('');
        $('#modalFestivoTitulo').text('Agregar día festivo');
        $('#modalFestivo').modal('show');
    },

    fnAbrirModalEditar: function (id) {
        var item = null;
        $.each(Modulo.festivos, function (i, f) {
            if (String(f.id) === String(id)) {
                item = f;
                return false;
            }
        });
        if (!item) return;

        $('#festivoId').val(item.id);
        $('#festivoFecha').val(item.fecha);
        $('#festivoDescripcion').val(item.descripcion);
        $('#modalFestivoTitulo').text('Editar día festivo');
        $('#modalFestivo').modal('show');
    },

    fnGuardar: function () {
        var id = $('#festivoId').val();
        var fecha = $('#festivoFecha').val();
        var descripcion = $('#festivoDescripcion').val().trim();

        if (!fecha || !descripcion) {
            alert('Favor de capturar fecha y descripción');
            return;
        }

        var esEdicion = !!id;
        var varURL = Modulo.baseurl + 'controllerDiasFestivos/' + (esEdicion ? 'actualizar' : 'guardar');
        var datos = { fecha: fecha, descripcion: descripcion };
        if (esEdicion) datos.id = id;

        $.ajax({
            url: varURL,
            type: 'post',
            data: datos,
            dataType: 'json'
        }).done(function (resp) {
            if (resp.exito) {
                $('#modalFestivo').modal('hide');
                Modulo.fnCargarFestivos($('#filtroAnio').val());
            } else {
                alert(resp.mensaje);
            }
        }).fail(function () {
            alert('Error al guardar el día festivo');
        });
    },

    fnEliminar: function (id) {
        if (!confirm('¿Seguro que quieres eliminar este día festivo?')) return;

        var varURL = Modulo.baseurl + 'controllerDiasFestivos/eliminar';
        $.ajax({
            url: varURL,
            type: 'post',
            data: { id: id },
            dataType: 'json'
        }).done(function (resp) {
            if (resp.exito) {
                Modulo.fnCargarFestivos($('#filtroAnio').val());
            } else {
                alert(resp.mensaje);
            }
        }).fail(function () {
            alert('Error al eliminar el día festivo');
        });
    },

    fnGenerar: function () {
        var anio = $('#filtroAnio').val();
        if (!anio) {
            alert('Selecciona un año primero');
            return;
        }
        if (!confirm('¿Generar los festivos oficiales de ' + anio + '? No se duplican los que ya existan.')) return;

        var varURL = Modulo.baseurl + 'controllerDiasFestivos/generar';
        $.ajax({
            url: varURL,
            type: 'post',
            data: { anio: anio },
            dataType: 'json'
        }).done(function (resp) {
            alert(resp.mensaje);
            Modulo.fnCargarFestivos(anio);
        }).fail(function () {
            alert('Error al generar los festivos');
        });
    },

    fnInicializar: function () {
        Modulo.fnCargarAnios();
        Modulo.fnCargarFestivos($('#filtroAnio').val());

        $('#filtroAnio').on('change', function () {
            Modulo.fnCargarFestivos($(this).val());
        });

        $('#btnNuevo').on('click', function () {
            Modulo.fnAbrirModalNuevo();
        });

        $('#btnGuardarFestivo').on('click', function () {
            Modulo.fnGuardar();
        });

        $('#btnGenerar').on('click', function () {
            Modulo.fnGenerar();
        });

        $(document).on('click', '.btnEditar', function () {
            Modulo.fnAbrirModalEditar($(this).data('id'));
        });

        $(document).on('click', '.btnEliminar', function () {
            Modulo.fnEliminar($(this).data('id'));
        });
    }
};

$(document).ready(function () {
    Modulo.fnInicializar();
});
