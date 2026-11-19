<meta charset="UTF-8">
<link href='assets/css/select2.min.css' rel='stylesheet' type='text/css'>
<script src='assets/js/select2.min.js'></script>

<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<link href='assets/css/relojCSS.css' rel='stylesheet' type='text/css'>
<script src="assets/js/relojJS.js"></script>
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
</style>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Asignacion de Horarios x Empresa</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="container">
                          <div class="row">              
                              <div id="asignar" class="col-md-12 text-right" style="display:none;">
                                  <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#ModificarDia" >
                                      <i class="fa fa-plus-circle"></i> Asignar Horario</button>
                                  </button>  
                              </div> 
                          </div>
                                                              
                          <div class="row">
                                <div class="col-12">   
                                    <br/>
                                </div>
                            </div>                    
                            <div class="col-md-8">
                                    <label style="font-weight: bold!important;">Empresa</label>
                                            <select class="custom-select col-12" id="id_empresa" required></select>
                            </div>                                         
                            <div class="row">
                                <div class="col-12">   
                                    <br/>
                                    <br/>
                                </div>
                            </div>    
                          <div class="row">
                            <div class="col-md-12">
                              <!-- Nav tabs -->
                              <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                  <a class="nav-link active" data-toggle="tab" href="#lunestab">Lunes</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#martestab">Martes</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#miercolestab">Miercoles</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#juevestab">Jueves</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#viernestab">Viernes</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#sabadotab">Sabado</a>
                                </li>
                                <li class="nav-item">
                                  <a class="nav-link" data-toggle="tab" href="#domingotab">Domingo</a>
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
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                    </tr>
                                                </thead>
                                                <tfoot>
                                                    <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                  </div>
                                  <div id="martestab" class="container tab-pane"><br>
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaMartes">
                                          <div class="col-12">
                                              <table id="martes" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                  <thead>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </thead>
                                                  <tfoot>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </tfoot>
                                              </table>
                                          </div>
                                      </div>
                                  </div>
                                  <div id="miercolestab" class="container tab-pane"><br>
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaMiercoles">
                                          <div class="col-12">
                                              <table id="miercoles" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                  <thead>
                                                      
                                                      <tr>
														<th>Turno</th>
														<th>Descripcion</th>
														<th>Entrada</th>
														<th>Salida</th>
														
														<th>Fecha Asignacion</th>
														<th>Opciones</th>
													</tr>
                                                  </thead>
                                                  <tfoot>
                                                      <tr>
														<th>Turno</th>
														<th>Descripcion</th>
														<th>Entrada</th>
														<th>Salida</th>
														
														<th>Fecha Asignacion</th>
														<th>Opciones</th>
													</tr>
                                                  </tfoot>
                                              </table>
                                          </div>
                                      </div>
                                  </div>
                                  <div id="juevestab" class="container tab-pane"><br>
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaJueves">
                                          <div class="col-12">
                                              <table id="jueves" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                  <thead>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </thead>
                                                  <tfoot>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </tfoot>
                                              </table>
                                          </div>
                                      </div>
                                  </div>
                                  <div id="viernestab" class="container tab-pane"><br>
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaViernes">
                                          <div class="col-12">
                                              <table id="viernes" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                  <thead>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </thead>
                                                  <tfoot>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </tfoot>
                                              </table>
                                          </div>
                                      </div>
                                  </div>
                                  <div id="sabadotab" class="container tab-pane"><br>
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaSabado">
                                          <div class="col-12">
                                              <table id="sabado" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                  <thead>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        <th>Fecha Asignacion</th>
                                                        
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </thead>
                                                  <tfoot>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </tfoot>
                                              </table>
                                          </div>
                                      </div>
                                  </div>
                                  <div id="domingotab" class="container tab-pane"><br>
                                    <div class="row">
                                        <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" style="display:none;"></div>                                        
                                            </center>
                                        </div>
                                    </div>                                  
                                    <div class="row" id="tablaDomingo">
                                          <div class="col-12">
                                              <table id="domingo" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                                  <thead>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
                                                        <th>Opciones</th>
                                                      </tr>
                                                  </thead>
                                                  <tfoot>
                                                      <tr>
                                                        <th>Turno</th>
                                                        <th>Descripcion</th>
                                                        <th>Entrada</th>
                                                        <th>Salida</th>
                                                        
                                                        <th>Fecha Asignacion</th>
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
                <h4 class="modal-title" id="myModal_categoriasLabel"></h4>
            </div>
            <div class="modal-body">
                <div class="container">  
                    <fieldset id="formulario">
                        <input id="id" type="text" value="0" style="display:none;">
                        <div class="panel panel-default">
                        <div class="panel-heading">.</div>
                            <div class="panel-body">                                
                                <table>
                                    <td>
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkLunes" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Lunes</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioLunes" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>  
                                        <th>Tipo
                                                <input type="text" disabled="disabled" id="tipoLunes">                                                    
                                        </th>                  
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaLunes">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaLunes">
                                        </th>
                                        </tr>
                                        
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkMartes" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Martes</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioMartes" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>      
                                        <th>Tipo
                                                <input type="text" disabled="disabled" id="tipoMartes">                                                    
                                        </th>              
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaMartes">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaMartes">
                                        </th>
                                        </tr>
                                        
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkMiercoles" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Miercoles</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioMiercoles" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>        
                                        <th>Tipo
                                                <input type="text" disabled="disabled" id="tipoMiercoles">                                                    
                                        </th>                
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaMiercoles">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaMiercoles">
                                        </th>											
                                        </tr>
                                        
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkJueves" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Jueves</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioJueves" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>     
                                        <th>Tipo
                                                <input type="text" disabled="disabled" id="tipoJueves">                                                    
                                        </th>                    
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaJueves">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaJueves">
                                        </th>											
                                        </tr>
                                        
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkViernes" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Viernes</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioViernes" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>     
                                        <th>Tipo
                                                <input type="text" disabled="disabled" id="tipoViernes">                                                    
                                        </th>                  
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaViernes">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaViernes">
                                        </th>
                                        </tr>                                        
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkSabado" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Sabado</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioSabado" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>           
                                        <th>Tipo
                                                <input type="text" disabled="disabled" id="tipoSabado">                                                    
                                        </th>             
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaSabado">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaSabado">
                                        </th>
                                        </tr>                                
                                        <tr>
                                        <th>
                                            <label class="custom-control custom-checkbox">
                                                <input id="checkDomingo" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Domingo</span> </label>
                                                <div class="help-block">
                                                </div>
                                        </th>
                                        <th>Horario
                                            <select  class="required" required id="horarioDomingo" style="width: 204px!important;">
                                            <option value="0">Selecciona el Horario</option>
                                            </select>
                                        </th>
                                        <th>Tipo
                                                <input type="text" id="tipoDomingo" disabled="disabled">         
                                        </th>
                                        <th>Entrada
                                                <input type="time" disabled="disabled" id="entradaDomingo">
                                        </th>
                                        <th>Salida
                                            <input type="time" disabled="disabled" id="salidaDomingo">
                                        </th>
                                        </tr>   
                                    </td>
                                </table>
                            </div>
                        </div>
                        <label style="font-weight: bold!important;">Fecha de Asignación:</label> 
                        <div id="datepicker" class="input-group date" data-date-format="yyyy-mm-dd">
                            <input id="fechaAsignacion" class="form-control" type="text" readonly />
                            <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                        </div>
                        <label style="font-weight: bold!important;">Descripción:</label> 
                        <input type="text" id="descripcion" class="form-control valid">  

                        <label style="font-weight: bold!important;">Cantidad de dias por Semana:</label> 
                        <input type="number" id="cantidad" class="form-control valid" step="0.01" min="0" max="7" placeholder="Ej. 6 o 5.5 (admite decimales)">

                        <center>
                    </fieldset>
                </div>    
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                <button id="btn_guardar" class="submit btn btn-success" type="button" value="Submit">Guardar</button> 
            </div>
        </div>
    </div>
</div>

<!-- ¿A quién se le asigna el horario? (assets/js/horariosEmpresaAplicar.js) -->
<div class="modal fade" id="modalAplicarA" tabindex="-1" role="dialog" style="z-index: 1060;">
    <div class="modal-dialog modal-lg" role="document" style="max-width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-users"></i> ¿A quién se le asigna este horario? <small class="text-muted" id="aplicarEmpresa"></small></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:8px;">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn aplicar-modo" id="aplicarTodos" data-modo="todos"><i class="fa fa-users"></i> A todos</button>
                        <button type="button" class="btn aplicar-modo" id="aplicarAlgunos" data-modo="algunos"><i class="fa fa-check-square-o"></i> Solo a algunos</button>
                    </div>
                    <input type="text" class="form-control form-control-sm" id="aplicarBuscar" placeholder="Buscar número, nombre, departamento u horario" style="max-width: 320px;">
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="aplicar-grupo">
                            <div class="aplicar-cab" id="aplicarTodosNormales" data-grupo="normales" title="Marcar / desmarcar todos">
                                <i class="fa fa-square-o aplicar-caja"></i> <i class="fa fa-sun-o"></i> <b>Turno normal</b> (<span id="aplicarCuentaNormales">0</span>)
                            </div>
                            <div class="aplicar-lista" id="aplicarNormales"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="aplicar-grupo aplicar-grupo-noche">
                            <div class="aplicar-cab" id="aplicarTodosNocturnos" data-grupo="nocturnos" title="Marcar / desmarcar todos">
                                <i class="fa fa-square-o aplicar-caja"></i> <i class="fa fa-moon-o"></i> <b>Turno nocturno</b> (<span id="aplicarCuentaNocturnos">0</span>)
                            </div>
                            <div class="aplicar-lista" id="aplicarNocturnos"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <span id="aplicarResumen"></span>
                <div>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="aplicarGuardar" style="color:#fff !important;"><i class="fa fa-check"></i> Asignar horario</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ventana "¿A quién se le asigna?" */
.aplicar-grupo { border: 1px solid rgba(0, 0, 0, .12); border-radius: 6px; overflow: hidden; }
.aplicar-cab { padding: 8px 12px; background: rgba(30, 136, 229, .1); cursor: pointer; user-select: none; }
.aplicar-grupo-noche .aplicar-cab { background: rgba(116, 96, 238, .15); }
.aplicar-lista { max-height: 380px; overflow-y: auto; padding: 4px; }
.aplicar-fila { display: flex; gap: 8px; align-items: flex-start; padding: 5px 8px; border-radius: 4px; cursor: pointer; font-size: 13px; line-height: 1.3; user-select: none; }
.aplicar-fila:hover { background: rgba(30, 136, 229, .08); }
.aplicar-caja { font-size: 17px; width: 16px; margin-top: 1px; color: #8a96a3; }
.aplicar-fila.sel .aplicar-caja, .aplicar-cab.sel .aplicar-caja { color: #1e88e5; }
.aplicar-modo { border: 1px solid #1e88e5; color: #1e88e5; background: transparent; }
.aplicar-modo.activo { background: #1e88e5; color: #fff !important; }
html.modo-nocturno .aplicar-grupo { border-color: #3a4049; }
/* botones de la pregunta de horarios nocturnos (horariosEmpresa.js) */
.swal-button.swal-boton-verde { background-color: #26a69a; color: #fff; }
.swal-button.swal-boton-verde:not([disabled]):hover { background-color: #1e8d83; }
.swal-button.swal-boton-rojo { background-color: #ef5350; color: #fff; }
.swal-button.swal-boton-rojo:not([disabled]):hover { background-color: #d84542; }
</style>
<script src="assets/js/horariosEmpresa.js?v=<?php echo filemtime(FCPATH.'assets/js/horariosEmpresa.js'); ?>"></script>
<script src="assets/js/horariosEmpresaAplicar.js?v=<?php echo filemtime(FCPATH.'assets/js/horariosEmpresaAplicar.js'); ?>"></script>
<script src="assets/js/horariosEmpresaVer.js?v=<?php echo filemtime(FCPATH.'assets/js/horariosEmpresaVer.js'); ?>"></script>
<style>
/* "Editar / ver empleados" (assets/js/horariosEmpresaVer.js) */
#modalVerHorario .modal-xl { max-width: 1250px; }
#modalVerHorario .ver-horario-barra { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
#modalVerHorario .ver-horario-barra input { max-width: 360px; }
#modalVerHorario .ver-horario-tabla { max-height: 60vh; overflow: auto; }
#modalVerHorario table { font-size: 12px; }
#modalVerHorario thead th { position: sticky; top: 0; background: #f4f6f9; z-index: 1; white-space: nowrap; }
#modalVerHorario td { vertical-align: middle; }
#modalVerHorario td:last-child { white-space: nowrap; }
#modalVerHorario .ver-anterior td { opacity: .75; }
#modalVerHorario .ver-vigencia .badge, #modalVerHorario .badge-danger { color: #fff !important; font-size: 10px; }
#modalVerHorario .ver-filtro { border: 1px solid #1e88e5; color: #1e88e5; background: transparent; }
#modalVerHorario .ver-filtro.activo { background: #1e88e5; color: #fff !important; }
#modalVerHorario .ver-sel { width: 30px; text-align: center; cursor: pointer; user-select: none; }
#modalVerHorario .ver-caja { font-size: 16px; color: #8a96a3; }
#modalVerHorario tr.sel td { background: rgba(255, 178, 43, .12); }
#modalVerHorario tr.sel .ver-caja, #modalVerHorario thead .fa-check-square { color: #ffb22b; }
#modalVerHorario .ver-masivo-barra { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 8px; font-size: 13px; }
#verMasivo { margin-top: 10px; padding: 12px; border: 1px solid #ffb22b; border-radius: 6px; background: rgba(255, 178, 43, .06); }
#verMasivo h6 { font-weight: bold; margin-bottom: 4px; }
#verMasivo .ver-masivo-ayuda { font-size: 12px; color: #888; margin-bottom: 8px; }
#verMasivo .ver-masivo-dias { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 6px 12px; }
#verMasivo .ver-masivo-dia label, #verMasivo .ver-masivo-campos label { font-size: 12px; font-weight: bold; margin-bottom: 2px; }
#verMasivo .ver-masivo-campos { display: grid; grid-template-columns: 190px 150px 1fr; gap: 12px; margin-top: 10px; }
#verMasivo .ver-masivo-botones { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; }
@media (max-width: 767px) { #verMasivo .ver-masivo-campos { grid-template-columns: 1fr; } }
html.modo-nocturno #verMasivo .form-control { background: #1f2329; color: #dfe3e8; border-color: #444b55; }
html.modo-nocturno #verMasivo .ver-masivo-ayuda { color: #9aa3ad; }
#modalVerHorario .ver-nombre { font-weight: bold; font-size: 11px; white-space: nowrap; }
#modalVerHorario .ver-horas { white-space: nowrap; }
#modalVerHorario .ver-descanso { color: #999; font-style: italic; }
#modalVerHorario .ver-hoy { background: rgba(30, 136, 229, .12) !important; }
#modalVerHorario .ver-horario-nota { font-size: 12px; color: #888; margin-top: 8px; }
html.modo-nocturno #modalVerHorario thead th { background: #2f353d; }
html.modo-nocturno #modalVerHorario .ver-hoy { background: rgba(79, 195, 247, .15) !important; }
</style>
<script src="assets/js/calendarioEsp.js"></script>

<script>
    $(function () {
        $("#fechaAsignacion").datepicker({
            daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
    });  

  $(document).ready(function(){
    $("#id_empresa").select2();
    $("#id_empresa").on("change", function () {
        if ($(this).val()) {
            $("#asignar").fadeIn();
        } else {
            $("#asignar").hide();
        }
    });
});
</script>
