<meta charset="UTF-8">


<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Captura de Incidencias</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="container">   
                          <div class="row">              
                              <div id="asignar" class="col-md-12 text-right" style="display:none;">
                                  <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#ModificarDia" >
                                      <i class="fa fa-plus-circle"></i> Agregar Incidencia</button>
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
<script src="assets/js/incidencias.js"></script>
<script src="assets/js/calendarioEsp.js"></script>
<script>
    $(function () {
        $("#fechaInicio").datepicker({
            startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
           // daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
        
        $("#fechaFin").datepicker({
            startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
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

		




