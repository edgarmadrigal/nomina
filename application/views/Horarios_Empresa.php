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
                                            <select class="custom-select col-12" id="id_empresa" required>
                                                <option value="" disabled selected>Selecciona una planta</option>
                                                <option value="1" > ACABADOS_ESPECIALES				</option>
                                                <option value="2" > ADMINISTRATIVO_MMS				</option>
                                                <option value="3" > CORTE							</option>
                                                <option value="4" > DESARROLLO						</option>
                                                <option value="5" > IBAMENSUAL						</option>
                                                <option value="6" > LAVANDERIA						</option>
                                                <option value="7" > PLANCHAS						</option>
                                                <option value="8" > PROCESOS_SECOS					</option>
                                                <option value="9" > SEGUNDAS						</option>
                                                <option value="10"> TERMINADO						</option>
                                                <option value="11"> NAZARENO1						</option>
                                                <option value="12"> NAZARENO2						</option>
                                                <option value="13"> NAZARENO3						</option>
                                                <option value="14"> CUENCAME						</option>
                                                <option value="15"> PILAR							</option>
                                                <option value="16"> DTL3							</option>
                                                <option value="17"> DTL								</option>
                                                <option value="18"> DTLNOM2							</option>
                                                <option value="19"> ADMINISTRATIVO_TBG				</option>
                                                <option value="20"> CUENCAME_CROM					</option>
                                                <option value="21"> PEDRICEÑA						</option>
                                                <option value="22"> SYSEL_NOM						</option>
                                                <option value="25"> EVENTUALES						</option>
                                                <option value="26"> LEON_GUZMAN						</option>
                                                <option value="27"> TCT								</option>
                                                <option value="28"> LEON_GUZMAN_ALMOHADAS			</option>		
                                                <option value="29"> DTLNOM_MENS						</option>			
                                                <option value="30"> ARCINAS							</option>			
                                                <option value="32"> TRANSPORTELAGOMA_NOM			</option>			
                                                <option value="33"> DELTA2							</option>			
                                                <option value="34"> DELTA3							</option>			
                                                <option value="35"> DTLNOM							</option>			
                                                <option value="36"> DTLNOM_MENS						</option>							
                                                <option value="37"> DTLNOM2_						</option>			
                                                <option value="38"> DTL3_							</option>			
                                                <option value="39"> TLAHUALILO						</option>			
                                                <option value="40"> DELTA2							</option>			
                                                <option value="41"> TBG								</option>	 
                                </select>
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
                        <input type="number" id="cantidad" class="form-control valid">

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

<script src="assets/js/horariosEmpresa.js"></script>
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
