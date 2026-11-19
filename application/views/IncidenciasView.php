<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>
<div class="page-wrapper">   

    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Captura de Incidencias</h4>
                <!-- Vacaciones e incapacidades de Microsip (application/sql/microsip_incidencias.sql) -->
                <div class="card">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between" style="gap:8px;">
                        <div style="font-size:13px;">
                            <b><i class="fa fa-refresh"></i> Vacaciones e incapacidades de Microsip</b><br>
                            <span id="microsipEstado" class="text-muted">Consultando...</span>
                        </div>
                        <button type="button" class="btn btn-primary" id="btnTraerMicrosip" style="color:#fff !important;">
                            <i class="fa fa-download"></i> Traer de Microsip
                        </button>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">     
                        <div class="container">   
                          <div class="row">              
                              <div class="col-md-12 text-right mb-2">
                                  <button type="button" id="btnVarios" class="btn btn-primary" style="color:#fff !important;">
                                      <i class="fa fa-users"></i> Incidencia a varios empleados</button>
                              </div>
                              <div id="asignar" class="col-md-12 text-right" style="display:none;">
                                  <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#ModificarDia" >
                                      <i class="fa fa-plus-circle"></i> Agregar Incidencia</button>
                                  </button>  
                              </div> 
                              <!---
                              <button id="correo" class="btn btn-info" data-toggle="modal" data-target="#MandarCorreo">
                                      <i class="fa fa-paper-plane"></i> MandaCorreo</button>
                                  </button>  
                                  -->
                          </div>                      
                          <div class="row">
                                <div class="col-12">   
                                    <br/>
                                </div>
                            </div>                  
                          <div class="row">           
                            <div class="col-6">
                                    <label style="font-weight: bold!important;">Empresa: </label>
                                            <select class="custom-select col-10" id="empresa_id" required></select>
                            </div>                   
                            <div class="col-6">
                                <label style="font-weight: bold!important;"> Empleado: </label> 
                                <select class="custom-select col-10" id='selEmpleado'  required>
                                    <option value='0'>- Selecciona un Empleado -</option>
                                </select>
                                <small id="fechaIngreso" class="text-info d-block mt-1" style="font-weight:600;"></small>
                            </div>
                            </div>                                        
                            <div class="row">
                                <div class="col-12">   
                                    <br/>
                                    <br/>
                                </div>
                            </div>    
                          <div class="row">
                            <div class="col-md-12">
                              <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                  <a class="nav-link active" data-toggle="tab" href="#lunestab">Incidencias</a>
                                </li>
                              </ul>
                              <!-- Tab panes -->
                                <div class="tab-content">
                                  <div id="lunestab" class="container tab-pane active"><br> 
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaLunes">
                                        <div class="col-12">
                                            <table id="lunes" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Descripcion</th>
                                                        <th>Desc. Corta</th>
                                                        <th>Fechha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Opciones</th>
                                                    </tr>
                                                </thead>
                                                <tfoot>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Descripcion</th>
                                                        <th>Desc. Corta</th>
                                                        <th>Fechha Inicio</th>
                                                        <th>Fecha Fin</th>
                                                        <th>Opciones</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                  </div>
                              </div>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>    
 
<!-- ============================================================== -->    
<!-- End Container fluid  -->
<!-- ============================================================== -->
<div class="modal fade" id="ModificarDia" tabindex="-1" role="dialog" aria-labelledby="myModal_categoriasLabel" aria-hidden="true">
     <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModal_categoriasLabel">AGREGAR</h4>
            </div>
            <div class="modal-body">
                <div class="container">  
                    <fieldset id="formulario">
                        <input id="id" type="text" value="0" style="display:none;">
                        <table>
                            <td>
                                <tr>
                                    <th>
                                    </th>  
                                    <th>                                                   
                                    </th> 
                                </tr>                                
                            </td>
                        </table>
                        <div class="row">
                            <div class="col-md-8">
                                            <label style="font-weight: bold!important;">Incidencia:</label> 
                                            <select  class="required" required id="concepto_id" style="float: left;position: relative;width: inherit;height: 40px;">
                                            <option value="0">Selecciona la incidencia</option>
                                            </select>
                            </div>
                            <div class="col-md-4">
                                            <label style="font-weight: bold!important;">Descripcion Corta:</label> 
                                            <input type="text" disabled="disabled" id="descripcionCorta"style="float: left;position: relative;width: inherit;height: 40px;">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <label style="font-weight: bold!important;">Fecha de Incio:</label> 
                                <div id="datepicker" class="input-group date" data-date-format="yyyy-mm-dd">
                                    <input id="fechaInicio" class="form-control" type="text" readonly />
                                    <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <label style="font-weight: bold!important;">Fecha de Fin:</label> 
                                <div id="datepicker" class="input-group date" data-date-format="yyyy-mm-dd">
                                    <input id="fechaFin" class="form-control" type="text" readonly />
                                    <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 cubre-bloque">
                                <label style="font-weight: bold!important;">¿Qué cubre la incidencia?</label>
                                <a href="#" class="cubre-todo">Todo el día</a>
                                <div class="cubre-opciones">
                                    <button type="button" class="btn cubre-op" data-campo="cubreEntrada"><i class="fa fa-check-square"></i> Entrada</button>
                                    <button type="button" class="btn cubre-op" data-campo="cubreEntradaComedor"><i class="fa fa-check-square"></i> Entrada de comida</button>
                                    <button type="button" class="btn cubre-op" data-campo="cubreSalidaComedor"><i class="fa fa-check-square"></i> Salida de comida</button>
                                    <button type="button" class="btn cubre-op" data-campo="cubreSalida"><i class="fa fa-check-square"></i> Salida</button>
                                </div>
                                <small class="text-muted cubre-ayuda"></small>
                            </div>
                        </div>
                        <label style="font-weight: bold!important;">REG IMSS:</label> 
                        <input type="text" disabled="disabled" id="regimss" class="form-control valid">  

                        <center>
                    </fieldset>
                </div>    
            </div>
            <div class="modal-footer">
                    <!--<button id="btn_guardar" type="button" class="btn btn-success">Aceptar</button>-->
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                <button id="btn_guardar" class="submit btn btn-success" type="submit" value="Submit">Guardar</button> 
            </div>
        </div>
    </div>
</div>


<link href='assets/css/select2.min.css' rel='stylesheet' type='text/css'>
<script src='assets/js/select2.min.js'></script>
<style>
    .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
        color: #ffffff!important;
        background-color: #2f3d4a!important;
        border-color: #20aee3 #20aee3 #007bff!important;
    }
    .custom-control {
        margin-bottom: -24px!important;
    }
    .select2-container--default .select2-results>.select2-results__options {
        max-height: 428px!important;
        overflow-y: auto;
    }
    #horarioLunes{
        font-size:15px;
    }
</style>
<style>
#ModificarDia .cubre-bloque { margin: 10px 0 6px; }
#ModificarDia .cubre-todo { margin-left: 8px; font-size: 12px; }
#ModificarDia .cubre-opciones { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0; }
#ModificarDia .cubre-op { border: 1px solid #1e88e5; color: #1e88e5; background: transparent; font-size: 13px; padding: 4px 10px; }
#ModificarDia .cubre-op .fa { width: 14px; }
#ModificarDia .cubre-op.activo { background: #1e88e5; color: #fff !important; }
#ModificarDia .cubre-ayuda { display: block; }
</style>
<!-- Incidencia a varios empleados: mismo concepto, días y checadas para los empleados marcados -->
<div class="modal fade" id="ModalVarios" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa fa-users"></i> Incidencia a varios empleados</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="var-tit">Empresas</label> <a href="#" id="varEmpTodas">Todas</a> · <a href="#" id="varEmpNinguna">Ninguna</a>
                    <div class="var-empresas" id="varEmpresas"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="var-tit">Incidencia</label>
                        <select class="custom-select" id="varConcepto"><option value="0">Cargando…</option></select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="var-tit">Días</label>
                        <div class="var-checadas">
                            <label class="var-chk"><input type="radio" name="varModo" value="uno" checked> Un solo día</label>
                            <label class="var-chk"><input type="radio" name="varModo" value="varios"> Varios días</label>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group"><label class="var-tit" id="varFiTit">Día</label><input type="date" class="form-control" id="varFi"></div>
                    <div class="col-md-3 form-group var-al"><label class="var-tit">Al</label><input type="date" class="form-control" id="varFf"></div>
                    <div class="col-md-6 form-group d-flex align-items-end"><small class="text-muted" id="varDias"></small></div>
                </div>
                <div class="form-group">
                    <label class="var-tit">¿Qué checadas cubre?</label> <a href="#" id="varTodoDia">Todo el día</a>
                    <div class="var-checadas">
                        <label class="var-chk"><input type="checkbox" class="varCubre" value="cubreEntrada" checked> Entrada</label>
                        <label class="var-chk"><input type="checkbox" class="varCubre" value="cubreEntradaComedor" checked> Entrada a comedor</label>
                        <label class="var-chk"><input type="checkbox" class="varCubre" value="cubreSalidaComedor" checked> Salida del comedor</label>
                        <label class="var-chk"><input type="checkbox" class="varCubre" value="cubreSalida" checked> Salida</label>
                    </div>
                    <small class="text-muted" id="varCubreAyuda"></small>
                </div>
                <label class="var-tit">Empleados</label>
                <div class="var-barra">
                    <input type="text" class="form-control form-control-sm" id="varBuscar" placeholder="Buscar número, nombre, puesto o departamento">
                    <label class="var-chk mb-0"><input type="checkbox" id="varTodos"> Marcar todos los que se ven</label>
                    <span id="varCuenta" class="var-cuenta"></span>
                </div>
                <div class="var-lista" id="varLista"><div class="text-muted p-2">Cargando…</div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="varGuardar"><i class="fa fa-save"></i> Guardar a los marcados</button>
            </div>
        </div>
    </div>
</div>
<style>
#ModalVarios .var-tit { font-weight: bold; }
#ModalVarios .var-checadas { display: flex; flex-wrap: wrap; gap: 6px 18px; margin: 4px 0; }
#ModalVarios .var-chk { font-weight: normal; cursor: pointer; margin: 0; }
/* El tema oculta los checkbox sueltos (left:-9999px); aquí se usan los nativos */
#ModalVarios input[type="checkbox"], #ModalVarios input[type="radio"] {
    position: static !important; left: auto !important; opacity: 1 !important; visibility: visible !important;
    width: 16px; height: 16px; margin: 0 6px 0 0; vertical-align: middle; cursor: pointer; accent-color: #1e88e5;
}
#ModalVarios .var-barra { display: flex; align-items: center; flex-wrap: wrap; gap: 8px 14px; margin-bottom: 6px; }
#ModalVarios .var-barra input[type="text"] { max-width: 340px; }
#ModalVarios .var-cuenta { font-weight: 600; color: #1e88e5; }
#ModalVarios .var-lista { max-height: 45vh; overflow: auto; border: 1px solid rgba(138,150,163,.35); border-radius: 4px; }
#ModalVarios .var-emp { display: flex; align-items: center; gap: 4px; padding: 5px 8px; margin: 0; cursor: pointer; border-bottom: 1px solid rgba(138,150,163,.2); font-weight: normal; }
#ModalVarios .var-emp:hover { background: rgba(30,136,229,.08); }
#ModalVarios .var-emp .var-num { min-width: 70px; font-weight: 600; }
#ModalVarios .var-empresas { display: flex; flex-wrap: wrap; gap: 4px 14px; max-height: 120px; overflow: auto; margin-top: 4px; padding: 6px 8px; border: 1px solid rgba(138,150,163,.35); border-radius: 4px; }
#ModalVarios .var-emp .var-empr { font-size: 11px; background: rgba(30,136,229,.12); color: #1e88e5; border-radius: 3px; padding: 1px 6px; white-space: nowrap; }
#ModalVarios .var-emp .var-det { margin-left: auto; font-size: 12px; color: #8a96a3; text-align: right; }
</style>
<script src="assets/js/incidencias.js?v=<?php echo filemtime(FCPATH.'assets/js/incidencias.js'); ?>"></script>
<script src="assets/js/incidenciasMicrosip.js?v=<?php echo filemtime(FCPATH.'assets/js/incidenciasMicrosip.js'); ?>"></script>
<script src="assets/js/calendarioEsp.js"></script>
<script>
    $(function () {
        $("#fechaInicio").datepicker({
           // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
           // daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
        
        $("#fechaFin").datepicker({
           // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
           // daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
    });  

  $(document).ready(function(){

    // Initialize select2
    
	$("#selEmpleado").select2({
		ajax: {
			url: Modulo.baseurl + 'horariosusuario/consultaEmpleado',
			type: "post",
			dataType: 'json',
			delay: 250,
			data: function (params) {
				return {
					searchTerm: params.term, // término de búsqueda
					empresa_id: $('#empresa_id').val() // se agrega la empresa
				};
			},
			processResults: function (response) {
				return {
					results: response
				};
			},
			cache: true
		}
	});

/*
    $("#selEmpleado").select2({
    ajax: { 
     url:Modulo.baseurl+'horariosusuario/consultaEmpleado',
     type: "post",
     dataType: 'json',
     delay: 250,
     data: function (params) {
      return {
        searchTerm: params.term // search term
      };
     },
     processResults: function (response) {
       return {
          results: response
       };
     },
     cache: true
    }
   });
   */
});
</script>

		




