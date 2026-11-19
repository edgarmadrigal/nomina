/**
 * Plantas del usuario: la principal (usuarios.idPlanta) y las que puede ver (dbo.UsuarioEmpresa,
 * vacío = todas; lo mismo que "Empresas" en Permisos). Los value de #planta son los id reales
 * (antes eran la posición en la lista + 1 y con huecos en los id se guardaba otra planta).
 */
var UsuPlantas = {
    plantas: [],
    esc: function (t) { return $('<div>').text(t == null ? '' : t).html(); },
    fnCargar: function (idUsuario, idPlanta) {
        $('#planta').html('<option value="">Cargando…</option>');
        $('#myModal_agregar .up-items').html('<em>Cargando…</em>');
        $('#myModal_agregar .up-buscar').val('');
        UsuPlantas.fnPonerModo('todas');
        var turno = UsuPlantas.turno = (UsuPlantas.turno || 0) + 1;
        $.when(
            $.post(base_url + 'usuarios/consultaPlanta', {}, null, 'json'),
            idUsuario > 0 ? $.post(base_url + 'usuarios/plantasUsuario', { id: idUsuario }, null, 'json') : $.Deferred().resolve([{ empresas: [] }]).promise()
        ).done(function (r1, r2) {
            if (turno !== UsuPlantas.turno) return;
            UsuPlantas.plantas = r1[0] || [];
            var tiene = {};
            $.each((r2[0] && r2[0].empresas) || [], function (i, e) { tiene[String(e)] = true; });
            var opciones = '<option value="">Selecciona</option>';
            $.each(UsuPlantas.plantas, function (i, p) { opciones += '<option value="' + Number(p.id) + '">' + UsuPlantas.esc(p.nombre) + '</option>'; });
            $('#planta').html(opciones).val(idPlanta ? String(idPlanta) : '');
            $('#myModal_agregar .up-items').html($.map(UsuPlantas.plantas, function (p) {
                return '<label class="up-item" data-texto="' + UsuPlantas.esc(String(p.nombre).toLowerCase()) + '"><input type="checkbox" value="' + Number(p.id) + '"' +
                    (tiene[String(p.id)] ? ' checked' : '') + '> ' + UsuPlantas.esc(p.nombre) + '</label>';
            }).join(''));
            if (Object.keys(tiene).length) UsuPlantas.fnPonerModo('elegir');
            UsuPlantas.fnPrincipal();
        });
    },
    fnPonerModo: function (modo) {
        $('#myModal_agregar .up-op').each(function () { $(this).toggleClass('activo', $(this).data('modo') === modo); });
        $('#myModal_agregar .up-lista').toggle(modo === 'elegir');
        UsuPlantas.fnCuenta();
    },
    fnModo: function () { return $('#myModal_agregar .up-op.activo').data('modo') || 'todas'; },
    /** La principal siempre va marcada y no se puede quitar. */
    fnPrincipal: function () {
        var p = $('#planta').val();
        $('#myModal_agregar .up-item').each(function () {
            var cb = $(this).find('input'), es = cb.val() === p;
            $(this).toggleClass('up-principal', es).attr('title', es ? 'Planta principal' : '');
            cb.prop('disabled', es);
            if (es) cb.prop('checked', true);
        });
        UsuPlantas.fnCuenta();
    },
    fnCuenta: function () {
        $('#myModal_agregar .up-cuenta').text($('#myModal_agregar .up-items input:checked').length + ' de ' + UsuPlantas.plantas.length);
    },
    /** Lo que se manda al guardar: '' = todas; si no, ids separados por coma (incluye la principal). */
    fnDatos: function () {
        if (UsuPlantas.fnModo() !== 'elegir') return '';
        var ids = $('#myModal_agregar .up-items input:checked').map(function () { return this.value; }).get();
        if ($('#planta').val() && ids.indexOf($('#planta').val()) < 0) ids.push($('#planta').val());
        return ids.join(',');
    }
};
$(document).on('click', '#myModal_agregar .up-op', function () { UsuPlantas.fnPonerModo($(this).data('modo')); });
$(document).on('change', '#planta', UsuPlantas.fnPrincipal);
$(document).on('change', '#myModal_agregar .up-items input', UsuPlantas.fnCuenta);
$(document).on('input', '#myModal_agregar .up-buscar', function () {
    var q = $.trim(this.value).toLowerCase();
    $('#myModal_agregar .up-item').each(function () { $(this).toggleClass('up-oculto', !!q && $(this).attr('data-texto').indexOf(q) < 0); });
});
/**
 * Contraseña en el formulario de usuarios: nunca se muestra la guardada (va cifrada).
 * Nuevo: obligatoria. Editar: opcional, vacía = se queda la misma.
 */
var Contrasena = {
    fnModo: function (nuevo) {
        var $p = $('#password');
        $p.prop('required', nuevo).toggleClass('required', nuevo)
          .attr('placeholder', nuevo ? 'contraseña (mínimo 8 caracteres)' : 'déjala vacía para no cambiarla')
          .removeClass('error');
        $('#password-error').hide();
    }
};
        jQuery.extend(jQuery.validator.messages, {
            required: "Campo obligatorio.",
            remote: "Please fix this field.",
            email: "Please enter a valid email address.",
            url: "Please enter a valid URL.",
            date: "Please enter a valid date.",
            dateISO: "Please enter a valid date (ISO).",
            number: "Please enter a valid number.",
            digits: "Please enter only digits.",
            creditcard: "Please enter a valid credit card number.",
            equalTo: "Please enter the same value again.",
            accept: "Please enter a value with a valid extension.",
            maxlength: jQuery.validator.format("Please enter no more than {0} characters."),
            minlength: jQuery.validator.format("Please enter at least {0} characters."),
            rangelength: jQuery.validator.format("Please enter a value between {0} and {1} characters long."),
            range: jQuery.validator.format("Please enter a value between {0} and {1}."),
            max: jQuery.validator.format("Please enter a value less than or equal to {0}."),
            min: jQuery.validator.format("Please enter a value greater than or equal to {0}.")
        });
        $("#formulario").validate();
     
        function validar(){    
            var usuario = $("#usuario").valid();
            var password = $("#password").valid();
            var nombre = $("#nombre").valid();
            var perfil = $("#perfil").valid();
            var planta = $("#planta").valid();
            
            if(usuario==true && password== true && nombre == true && planta== true){
                return true;
            }else{
                return false;
            }
        }
        function editar(id_edit) {
            $('#nombre').val('');
            $('#usuario').val(''); 
            $('#password').val('');
            $('#id').val(id_edit);
            Contrasena.fnModo(false);
            document.getElementById("drop-remove").checked = false;
            var x = document.getElementById("password");
            x.type = "password";   
             
            $.ajax({
                url: base_url+'Usuarios/consulta',
                type: 'post',
                dataType: 'json',
                data: {
                    id: id_edit
                }
            }).done(function (data) {
                if (data) {    
                    $('#perfil').empty();  
                    $('#nombre').val(data.nombre);
                    $('#usuario').val(data.usuario); 
                    $('#password').val(''); 
                    $('#id').val(data.id);
                    var perfil=data.idPerfil;
                    var planta=data.idPlanta;
                    $.ajax({
                        url:  base_url+'usuarios/consultaPerfil',
                        type: 'post',
                        dataType: 'json'
                    }).done(function (data){                             
                        perfiles= JSON.parse(JSON.stringify(data));

                        $('#perfil').append('<option value="">Selecciona</option>');
                        $.each(perfiles, function( index, value ) { 
                            $('#perfil').append('<option value="'+index+'">'+value.perfil+'</option>');
                        });   
                        
                        $("#perfil").val(perfil-1);                        
                        $('#id').val(id_edit);
                        $('#nombre').removeClass("error");
                        $('#usuario').removeClass("error");
                        $('#password').removeClass("error");
                        $('#perfil').removeClass("error");
                        $('#planta').removeClass("error");
                        $('#nombre-error').hide();
                        $('#usuario-error').hide();
                        $('#password-error').hide();
                        $('#perfil-error').hide();
                    });

                    UsuPlantas.fnCargar(id_edit, planta);
                    {
                        $("#perfil").val(perfil-1);
                        $('#id').val(id_edit);
                        $('#nombre').removeClass("error");
                        $('#usuario').removeClass("error");
                        $('#password').removeClass("error");
                        $('#perfil').removeClass("error");
                        $('#planta').removeClass("error");
                        $('#nombre-error').hide();
                        $('#usuario-error').hide();
                        $('#password-error').hide();
                        $('#perfil-error').hide();
                        $('#planta-error').hide();
                    }
                } else {
                    $('#nombre').val('');
                    $('#usuario').val(''); 
                    $('#password').val(''); 
                    $('#id').val('0');    
                }
            });
        }    
        // Cambiar solo la contraseña (la anterior no se puede ver: está cifrada)
        function cambiarPassword(id, usuario) {
            $('#passUsuarioId').val(id);
            $('#passUsuarioNombre').text(usuario);
            $('#passNueva').val(''); $('#passRepite').val('');
            $('#passVer').prop('checked', false);
            $('#passNueva, #passRepite').attr('type', 'password');
            $('#modalPassword').modal('show');
        }
        $(document).on('change', '#passVer', function () {
            $('#passNueva, #passRepite').attr('type', this.checked ? 'text' : 'password');
        });
        $(document).on('click', '#btnGuardarPassword', function () {
            var nueva = $('#passNueva').val(), repite = $('#passRepite').val();
            if (nueva.length < 8) { swal({ title: "Contraseña muy corta", text: "Debe tener al menos 8 caracteres.", icon: "warning" }); return; }
            if (nueva !== repite) { swal({ title: "No coinciden", text: "La contraseña y su repetición no son iguales.", icon: "warning" }); return; }
            var btn = $(this).prop('disabled', true);
            $.ajax({
                url: base_url + 'usuarios/cambiarPassword', type: 'post', dataType: 'json',
                data: { id: $('#passUsuarioId').val(), password: nueva }
            }).done(function (r) {
                if (r && r.exito) {
                    $('#modalPassword').modal('hide');
                    swal({ title: "Contraseña cambiada", text: r.mensaje, icon: "success" });
                } else {
                    swal({ title: "No se pudo", text: (r && r.mensaje) || "No se cambió la contraseña.", icon: "warning" });
                }
            }).fail(function () {
                swal({ title: "No se pudo", text: "No se pudo cambiar la contraseña.", icon: "error" });
            }).always(function () { btn.prop('disabled', false); });
        });

        // Ver el sistema como otro usuario (solo administrador). Entra con SUS permisos sin su contraseña.
        function verComo(id, usuario) {
            swal({
                title: '¿Ver el sistema como ' + usuario + '?',
                text: 'Vas a ver el sistema con los permisos, el menú y las plantas de "' + usuario + '", sin necesidad de su contraseña. Arriba aparecerá un botón para volver a tu cuenta cuando quieras.',
                icon: 'info',
                buttons: ['Cancelar', 'Ver como este usuario']
            }).then(function (ok) {
                if (!ok) return;
                $.ajax({ url: base_url + 'usuarios/suplantar', type: 'post', dataType: 'json', data: { id: id } })
                    .done(function (r) {
                        if (r && r.exito) { window.location = base_url + 'home'; }
                        else { swal({ title: 'No se pudo', text: (r && r.mensaje) || 'No se pudo ver como ese usuario.', icon: 'warning' }); }
                    })
                    .fail(function () { swal({ title: 'No se pudo', text: 'Error de conexión.', icon: 'error' }); });
            });
        }

        function eliminar(id_delete,usuario){
            swal({
                title: "Estas seguro?",
                text: "Se eliminara el usuario "+usuario,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            })
            .then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        url:  base_url+'usuarios/delete',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            id: id_delete
                        }
                    }).done(function (data) {
                        if (data && data.mensaje) {
                            swal({ title: "No se eliminó", text: data.mensaje, icon: "warning" });
                        } else if (data) {
                            // update the manageMemberTable
                            table.ajax.reload(null, false); 
                        } else {                            
                            // update the manageMemberTable
                            table.ajax.reload(null, false); 
                        }
                    });
                } else {
                    // update the manageMemberTable
                    table.ajax.reload(null, false); 
                }
            });
        }
        var table;
        
(function ($) {
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
        base_url="";
        //http://192.168.128.24:8080/nomina/
        TablaVacia = "No se encontraron resultados";
        usuariosTabla=[];
        $('#id').val(0);
        var buttonCommon = {
            exportOptions: {
                format: {
                    body: function ( data, row, column, node ) {
                        //
                        return data;/*column === 5 ?
                            data.replace( /[$,]/g, '' ) :
                            data;/**/
                    }
                }
            }
        };         
        table=$('#example').DataTable( {
            colReorder: true,
            responsive: true,
            'orders': [],
            "columnDefs": [
                { "orderable": false, "targets": 5 } ///no se puede ordenar por la Operacion que es la columna 4
                ],
            ajax: {
                type: "POST",
                url: base_url+'usuarios/consulta_usuario',
                contentType: 'application/json; charset=utf-8',
                data: function (data) {
                    usuariosTabla=data; 
                    return JSON.stringify(data); 
                }
            },
            "iDisplayLength": 10
            , "oLanguage": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "_MENU_ Registro por página",
                "zeroRecords": "No se encontraron resultados",
                "sEmptyTable": " " + TablaVacia,
                "sInfo": "Listado del _START_ al _END_ de _TOTAL_",
                "sInfoEmpty": "No se encontraron resultados",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix": "",
                "sSearch": "Filtrar:",
                "sUrl": "",
                "sInfoThousands": ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            }
            ,"oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
            ,"dom": '<"top"flBip<"clear">>rt<"bottom"flBip<"clear">>'
            ,buttons: [
                $.extend( true, {}, buttonCommon, {
                    extend: 'copyHtml5',
                    exportOptions: {
                        columns: [ 0, 1, 2, 3 ,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'excelHtml5',
                    exportOptions: {
                        columns:  [ 0, 1, 2, 3 ,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'csvHtml5',
                    exportOptions: {
                        columns:  [ 0, 1, 2, 3 ,4]
                    }
                } ),
                $.extend( true, {}, buttonCommon, {
                    extend: 'pdfHtml5',
                    exportOptions: {
                        columns:  [ 0, 1, 2, 3 ,4]
                    }
                } ),
            ]
        });
        $('#example tfoot th').each( function () {
            var title = $(this).text();
            if(title!="Opciones"){
                $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
            }else{
                $(this).html(title);
            }
        });
        $('#example thead th').each( function () {
            var title = $(this).text();
            if(title!="Opciones"){
                $(this).html( title+'<br><input type="text" placeholder="Buscar '+title+'" />' );
            }else{
                $(this).html(title);
            }
        });  
        table.columns().every( function () {
            var that = this;         
            $( 'input', this.header() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        });
        table.columns().every( function () {
            var that = this;
        
            $( 'input', this.footer() ).on( 'keyup change', function () {
                if ( that.search() !== this.value ) {
                    that
                        .search( this.value )
                        .draw();
                }
            } );
        });

        /*BOTON DE FORMULARIO... GUARDAR */
        $(document).on('click', '#btn_guardar', function (e) {
            e.preventDefault();
            var usuario = $('#usuario').val();       
            var nombre = $('#nombre').val();
            var password = $('#password').val();     
            var idPerfil = parseInt($('#perfil').val())+1;   
            var idPlanta= parseInt($('#planta').val());   // id real de la planta (ver UsuPlantas)
            var plantas = UsuPlantas.fnDatos();   
                       
            if(validar()){
                var id= $('#id').val();
                if(id>0){
                    ///GUARDAR EDICION
                    $.ajax({
                        url: base_url+'usuarios/edit',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'id': id,
                            'usuario': usuario,
                            'nombre':nombre,
                            'password': password,
                            'idPerfil': idPerfil,
                            'idPlanta': idPlanta,
                            'plantas': plantas,
                        }
                    }).done(function (data) {
                        if (data && data.mensaje) {
                            swal({ title: "No se guardó", text: data.mensaje, icon: "warning" });
                        } else if (data) {
                            swal({
                                    title: "Tus datos han sido actualizados",
                                    text: "Se actualizo correctamente",
                                    icon: "success",
                                })
                                .then((value) => {
                                        // hide the modal
                                        $("#myModal_agregar").modal('hide');
                                        // update the manageMemberTable
                                        table.ajax.reload(null, false); 
                                        document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });

                        } else {
                            swal({
                                    title: "Tus datos no han sido actualizados!",
                                    text: "No se actualizo correctamente",
                                    icon: "warning",
                                })
                                .then((value) => {
                                    window.location.assign( base_url+'usuarios');
                                    document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });
                        }
                    });

                }else{        
                    ///GUARDAR NUEVO        
                    $.ajax({
                        url: base_url+'usuarios/insert',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            'usuario': usuario,
                            'nombre':nombre,
                            'password': password,
                            'idPerfil': idPerfil,
                            'idPlanta': idPlanta,
                            'plantas': plantas,
                        }
                    }).done(function (data) {
                        if (data && data.mensaje) {
                            swal({ title: "No se guardó", text: data.mensaje, icon: "warning" });
                        } else if (data) {
                            swal({
                                    title: "Tus datos han sido registrados",
                                    text: "Se agrego correctamente",
                                    icon: "success",
                                })
                                .then((value) => {
                                        // hide the modal
                                        $("#myModal_agregar").modal('hide');
                                        // update the manageMemberTable
                                        table.ajax.reload(null, false); 
                                        document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });

                        } else {
                            swal({
                                    title: "Tus datos no han sido registrados!",
                                    text: "No se agrego correctamente",
                                    icon: "warning",
                                })
                                .then((value) => {
                                    window.location.assign( base_url+'usuarios');
                                    document.getElementById("drop-remove").checked = false;var x = document.getElementById("password");             x.type = "password";
                                });
                        }
                    });
                }
            }
        });

        /* BOTON DE LA TABLA ... AGREGAR  */
        $(document).on('click', '#nuevo', function (e) {            
            $('#nombre').val('');
            $('#usuario').val(''); 
            $('#password').val('');
            $('#id').val(0);
            Contrasena.fnModo(true);
            $('#perfil').empty();    
            $('#planta').empty();  
            var perfil = $("#perfil").valid();
            var planta = $("#planta").valid();
            document.getElementById("drop-remove").checked = false;
            var x = document.getElementById("password");            
             x.type = "password";
            $.ajax({
                url:  base_url+'usuarios/consultaPerfil',
                type: 'post',
                dataType: 'json'
            }).done(function (data) {                
                perfiles= JSON.parse(JSON.stringify(data));
                $('#perfil').append('<option value="">Selecciona</option>');
                $.each(perfiles, function( index, value ) { 
                    $('#perfil').append('<option value="'+index+'">'+value.perfil+'</option>');
                  });
            });
            
            UsuPlantas.fnCargar(0, null);
        });
    });
})(jQuery);