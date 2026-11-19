<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>
<div class="page-wrapper">   

    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Captura de Incidencias x Empresa</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="container">   
                          <div class="row">              
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
                                                        <th>Empleados</th>
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
                                                        <th>Empleados</th>
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
                        <div class="row">
                            <div class="col-md-12 aplica-bloque">
                                <label style="font-weight: bold!important;">¿A quién se aplica?</label>
                                <div class="aplica-modos">
                                    <button type="button" class="btn aplica-op activo" data-modo="todos"><i class="fa fa-users"></i> Todos los activos de la planta (<span class="aplica-total">0</span>)</button>
                                    <button type="button" class="btn aplica-op" data-modo="elegir"><i class="fa fa-user-plus"></i> Elegir empleados</button>
                                </div>
                                <div class="aplica-lista" style="display:none;">
                                    <div class="aplica-barra">
                                        <input type="text" class="form-control aplica-buscar" placeholder="Buscar número, nombre, puesto o departamento">
                                        <a href="#" class="aplica-marcar">Marcar los que se ven</a> ·
                                        <a href="#" class="aplica-desmarcar">Desmarcar los que se ven</a>
                                        <span class="aplica-cuenta"></span>
                                    </div>
                                    <div class="aplica-items"></div>
                                </div>
                            </div>
                        </div>

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


<!-- Quiénes tienen la incidencia de la empresa -->
<div class="modal fade" id="VerEmpleados" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Empleados con la incidencia <span class="ver-titulo"></span></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control ver-buscar" placeholder="Buscar número, nombre, puesto o departamento">
                <div class="ver-cuenta text-muted"></div>
                <div class="ver-tabla">
                    <table class="table table-sm table-hover">
                        <thead><tr><th>No.</th><th>Nombre</th><th>Puesto</th><th>Departamento</th><th></th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info ver-editar"><i class="fa fa-pencil"></i> Agregar / quitar empleados</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
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
    
	});

</script>

<style>
#ModificarDia .cubre-bloque { margin: 10px 0 6px; }
#ModificarDia .cubre-todo { margin-left: 8px; font-size: 12px; }
#ModificarDia .cubre-opciones { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0; }
#ModificarDia .cubre-op { border: 1px solid #1e88e5; color: #1e88e5; background: transparent; font-size: 13px; padding: 4px 10px; }
#ModificarDia .cubre-op .fa { width: 14px; }
#ModificarDia .cubre-op.activo { background: #1e88e5; color: #fff !important; }
#ModificarDia .cubre-ayuda { display: block; }
#ModificarDia .aplica-bloque { margin: 6px 0 10px; }
#ModificarDia .aplica-bloque > label { display: block !important; float: none !important; width: 100%; }
#ModificarDia .aplica-modos { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0; }
#ModificarDia .aplica-op { border: 1px solid #1e88e5; color: #1e88e5; background: transparent; font-size: 13px; padding: 4px 10px; }
#ModificarDia .aplica-op.activo { background: #1e88e5; color: #fff !important; }
#ModificarDia .aplica-barra { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 10px; margin: 6px 0 4px; font-size: 13px; }
#ModificarDia .aplica-barra .aplica-buscar { flex: 1 1 100%; height: 34px; }
#ModificarDia .aplica-cuenta { margin-left: auto; font-weight: bold; }
/* el tema esconde los checkbox nativos (left:-9999px); aquí se muestran */
/* el tema flota los label y pone los input en bloque al 100% */
#ModificarDia .aplica-item { float: none !important; }
#ModificarDia .aplica-item.aplica-oculto { display: none !important; }
#ModificarDia .aplica-item b { display: inline !important; }
#ModificarDia .aplica-item input[type=checkbox] { position: static !important; left: auto !important; opacity: 1 !important; visibility: visible !important; display: inline-block !important; width: auto !important; height: auto !important; flex: 0 0 auto; margin: 0 4px 0 0; }
#ModificarDia .aplica-items { max-height: 260px; overflow-y: auto; border: 1px solid rgba(128,128,128,.35); border-radius: 4px; padding: 4px 8px; }
#ModificarDia .aplica-item { display: flex !important; align-items: center; flex-wrap: wrap; gap: 0 6px; width: 100%; margin: 0; padding: 3px 0; font-weight: normal; cursor: pointer; border-bottom: 1px solid rgba(128,128,128,.15); }
#ModificarDia .aplica-item small { opacity: .7; }
#VerEmpleados .ver-tabla { max-height: 420px; overflow-y: auto; margin-top: 8px; }
#VerEmpleados .ver-cuenta { margin-top: 6px; font-size: 13px; }
.inc-empleados { white-space: nowrap; }
</style>
<script src="assets/js/incidenciasEmpresa.js?v=<?php echo filemtime(FCPATH.'assets/js/incidenciasEmpresa.js'); ?>"></script>
<script src="assets/js/calendarioEsp.js"></script>
		




