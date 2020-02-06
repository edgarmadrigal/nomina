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
                <h4 class="card-title">Asignacion de Horarios</h4> 
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
                                <label> Empleado: </label> 
                                <select id='selEmpleado' style="width: 450px;">
                                    <option value='0'>- Selecciona un Empleado -</option>
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
                <h4 class="modal-title" id="myModal_categoriasLabel">AGREGAR</h4>
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

<script src="assets/js/horariosUsuario.js"></script>
<script src="assets/js/calendarioEsp.js"></script>

<script>
    $(function () {
        $("#fechaAsignacion").datepicker({
            //startDate: '-7d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
            daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
    });  

  $(document).ready(function(){

    // Initialize select2
    $("#selPlanta").select2();

    // Read selected option
    $('#but_read').click(function(){
    var username = $('#selPlanta option:selected').text();
    });



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
});
</script>