/**
 * Catálogos > Empleados IBF (controllers/EmpleadosIBF.php).
 */
var EIBF = {
    filas: [],
    filtro: 'A',
    tabla: null,
    esc: function (t) { return $('<div>').text(t == null ? '' : String(t)).html(); },
    fnFecha: function (f) { if (!f) return ''; var p = String(f).split('-'); return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : f; },

    fnCargar: function () {
        $.post('EmpleadosIBF/lista', {}, null, 'json').done(function (r) {
            if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); return; }
            EIBF.filas = r.filas || [];
            $('#eiListaPuestos').html($.map(r.puestos || [], function (p) { return '<option value="' + EIBF.esc(p) + '">'; }).join(''));
            $('#eiListaDeptos').html($.map(r.departamentos || [], function (d) { return '<option value="' + EIBF.esc(d) + '">'; }).join(''));
            var act = EIBF.filas.filter(function (f) { return f.estatus === 'A'; }).length;
            $('#kActivos').text(act);
            $('#kBajas').text(EIBF.filas.length - act);
            $('#kTodos').text(EIBF.filas.length);
            $('#kDeptos').text((r.departamentos || []).length);
            EIBF.fnPintar();
        }).fail(function () { swal('No se pudo', 'No se pudo cargar el catálogo.', 'error'); });
    },

    fnPintar: function () {
        var lista = EIBF.filas.filter(function (f) { return !EIBF.filtro || f.estatus === EIBF.filtro; });
        var cuerpo = $.map(lista, function (f) {
            var activo = f.estatus === 'A';
            return '<tr><td data-order="' + Number(f.numero) + '"><b>' + EIBF.esc(f.numero) + '</b></td><td>' + EIBF.esc(f.nombreCompleto) + '</td><td>' + EIBF.esc(f.puesto) +
                '</td><td>' + EIBF.esc(f.departamento) + '</td><td>' + EIBF.esc(f.rfc) + '</td><td>' + EIBF.esc(f.imss) +
                '</td><td data-order="' + EIBF.esc(f.fechaIngreso) + '">' + EIBF.fnFecha(f.fechaIngreso) + '</td><td>' +
                (activo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Baja</span>') +
                '</td><td class="text-center text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-info ei-editar" data-id="' + f.id + '" title="Editar"><i class="fa fa-pencil"></i></button> ' +
                (activo ? '<button type="button" class="btn btn-sm btn-outline-danger ei-estatus" data-id="' + f.id + '" data-estatus="B" title="Dar de baja"><i class="fa fa-user-times"></i></button>'
                        : '<button type="button" class="btn btn-sm btn-outline-success ei-estatus" data-id="' + f.id + '" data-estatus="A" title="Reactivar"><i class="fa fa-user-plus"></i></button>') +
                '</td></tr>';
        }).join('');
        if (EIBF.tabla) { EIBF.tabla.destroy(); EIBF.tabla = null; }
        $('#eiTabla tbody').html(cuerpo || '<tr><td colspan="9" class="text-center text-muted">No hay empleados en este filtro.</td></tr>');
        if (!lista.length) return;
        EIBF.tabla = $('#eiTabla').DataTable({
            order: [[1, 'asc']], pageLength: 25, columnDefs: [{ targets: 8, orderable: false, searchable: false }],
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"B>>frtip',
            buttons: [
                { extend: 'copyHtml5', text: 'Copiar', exportOptions: { columns: ':not(:last)' } },
                { extend: 'excelHtml5', text: 'Excel', title: 'Empleados IBF', exportOptions: { columns: ':not(:last)' } },
                { extend: 'pdfHtml5', text: 'PDF', title: 'Empleados IBF', orientation: 'landscape', pageSize: 'LETTER', exportOptions: { columns: ':not(:last)' } }
            ],
            language: { sLengthMenu: '_MENU_ por página', sInfo: 'Del _START_ al _END_ de _TOTAL_', sInfoEmpty: 'Sin datos',
                sInfoFiltered: '(de _MAX_)', sSearch: 'Buscar:', sZeroRecords: 'No se encontraron empleados',
                oPaginate: { sFirst: 'Primero', sLast: 'Último', sNext: 'Siguiente', sPrevious: 'Anterior' } }
        });
    },

    fnAbrir: function (f) {
        $('#eiModalTitulo').text(f ? 'Editar empleado ' + f.numero : 'Agregar empleado');
        $('#eiId').val(f ? f.id : '');
        $('#eiNumero').val(f ? f.numero : '');
        $('#eiFecha').val(f ? (f.fechaIngreso || '') : '');
        $('#eiPaterno').val(f ? f.paterno : '');
        $('#eiMaterno').val(f ? f.materno : '');
        $('#eiNombres').val(f ? f.nombres : '');
        $('#eiPuesto').val(f ? f.puesto : '');
        $('#eiDepto').val(f ? f.departamento : '');
        $('#eiRfc').val(f ? f.rfc : '');
        $('#eiImss').val(f ? f.imss : '');
        $('#eiError').text('');
        EIBF.fnVista();
        $('#eiModal').modal('show');
        setTimeout(function () { $(f ? '#eiPaterno' : '#eiNumero').focus(); }, 400);
    },

    fnVista: function () {
        var p = $.trim($('#eiPaterno').val()).toUpperCase(), m = $.trim($('#eiMaterno').val()).toUpperCase(), n = $.trim($('#eiNombres').val()).toUpperCase();
        $('#eiVista').text(p || n ? p + '/' + m + ',' + n : '—');
    },

    fnGuardar: function () {
        var datos = {
            id: $('#eiId').val(), numero: $.trim($('#eiNumero').val()), fechaIngreso: $('#eiFecha').val(),
            paterno: $('#eiPaterno').val(), materno: $('#eiMaterno').val(), nombres: $('#eiNombres').val(),
            puesto: $('#eiPuesto').val(), departamento: $('#eiDepto').val(), rfc: $('#eiRfc').val(), imss: $('#eiImss').val()
        };
        if (!/^\d+$/.test(datos.numero)) { $('#eiError').text('El número de empleado debe ser solo dígitos.'); return; }
        if (!$.trim(datos.paterno) || !$.trim(datos.nombres)) { $('#eiError').text('Captura al menos el apellido paterno y el nombre.'); return; }
        var btn = $('#eiGuardar').prop('disabled', true);
        $.post('EmpleadosIBF/guardar', datos, null, 'json').done(function (r) {
            if (!r.exito) { $('#eiError').text(r.mensaje); return; }
            $('#eiModal').modal('hide');
            swal('Listo', r.mensaje, 'success');
            EIBF.fnCargar();
        }).fail(function () { $('#eiError').text('No se pudo guardar.'); })
          .always(function () { btn.prop('disabled', false); });
    },

    fnEstatus: function (id, estatus) {
        var f = EIBF.filas.filter(function (x) { return String(x.id) === String(id); })[0];
        if (!f) return;
        swal({
            title: estatus === 'B' ? '¿Dar de baja?' : '¿Reactivar?',
            text: f.numero + ' ' + f.nombreCompleto + (estatus === 'B' ? '\n\nNo se borra: se conserva su historial y se puede reactivar.' : ''),
            icon: 'warning', buttons: ['Cancelar', estatus === 'B' ? 'Dar de baja' : 'Reactivar'], dangerMode: estatus === 'B'
        }).then(function (ok) {
            if (!ok) return;
            $.post('EmpleadosIBF/estatus', { id: id, estatus: estatus }, null, 'json').done(function (r) {
                if (!r.exito) { swal('Aviso', r.mensaje, 'warning'); return; }
                EIBF.fnCargar();
            });
        });
    }
};

$(function () {
    EIBF.fnCargar();
    $('#eiNuevo').on('click', function () { EIBF.fnAbrir(null); });
    $('#eiGuardar').on('click', EIBF.fnGuardar);
    $('#eiModal').on('input', '#eiPaterno, #eiMaterno, #eiNombres', EIBF.fnVista)
                 .on('keydown', 'input', function (e) { if (e.which === 13) EIBF.fnGuardar(); });
    $('#eiNumero, #eiImss').on('input', function () { this.value = this.value.replace(/\D/g, ''); });
    $('#eiTabla').on('click', '.ei-editar', function () {
        var id = $(this).data('id');
        EIBF.fnAbrir(EIBF.filas.filter(function (x) { return String(x.id) === String(id); })[0]);
    }).on('click', '.ei-estatus', function () { EIBF.fnEstatus($(this).data('id'), $(this).data('estatus')); });
    $('#eibf').on('click', '.ei-filtro', function () {
        $('.ei-filtro').removeClass('activo');
        $(this).addClass('activo');
        EIBF.filtro = $(this).data('filtro');
        EIBF.fnPintar();
    });
});
