
<link href='assets/css/relojCSS.css' rel='stylesheet' type='text/css'>
<script src="assets/js/relojJS.js"></script>
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>
<script src="assets/js/calendarioEsp.js"></script>
<style>
.modal-header{
    display:none;
}
</style>
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Horarios</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">    
                                <div class="row">              
                                    <div class="col-md-12 text-right">
                                        <div class="messages"></div>
                                        <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#myModal_agregar" >
                                            <i class="fa fa-plus-circle"></i> Asignar horario</button>
                                        </button>  
                                    </div> 
                                </div>      
                                <div class="row">
                                    <div class="col-12">
                                        <table id="example" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <td></td>      
                                                    <td></td>    
                                                    <td COLSPAN=3 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;">Entrada</td>
                                                    <td COLSPAN=3  style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;">Salida</td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>   
                                                    <td></td>                                                   
                                                    <td></td>
                                                </tr>
                                                <tr>
                                                    <th>ID</th>     
                                                    <th>Nombre</th>     
                                                    <th style="border-left: inset!important;border-color: black;">Desde</th>
                                                    <th>Inicio</th>
                                                    <th>Hasta</th>
                                                    <th style="border-left: inset!important;border-color: black;">Desde</th>
                                                    <th>Inicio</th>
                                                    <th>Hasta</th>
                                                    <th style="border-left: inset!important;border-color: black;">Horas</th>
                                                    <th>Comedor</th>
                                                    <th>tiempoComida</th>
                                                    <th>Tipo</th>
                                                    <th>Fecha</th>                                                     
                                                    <th>Opciones</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>  
                                                    <th>Nombre</th>     
                                                    <th style="border-left: inset!important;border-color: black;">Desde</th>
                                                    <th>Inicio</th>
                                                    <th>Hasta</th>
                                                    <th style="border-left: inset!important;border-color: black;">Desde</th>
                                                    <th>Inicio</th>
                                                    <th>Hasta</th>
                                                    <th style="border-left: inset!important;border-color: black;">Horas</th>
                                                    <th>Comedor</th>
                                                    <th>tiempoComida</th>
                                                    <th>Tipo</th>
                                                    <th>Fecha</th>                                                     
                                                    <th>Opciones</th>
                                                </tr>
                                                <tr>
                                                    <td></td>    
                                                    <td></td>      
                                                    <td COLSPAN=3 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;">Entrada</td>
                                                    <td COLSPAN=3  style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;">Salida</td>
                                                    <td></td>
                                                    <td></td> 
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>                                                     
                                                    <td></td>
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
<div class="modal fade" id="myModal_agregar" tabindex="-1" role="dialog" aria-labelledby="myModal_categoriasLabel"
        aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="myModal_categoriasLabel">AGREGAR</h4></div>
            <div class="modal-body">
                <div class="container">      
                <form id="formulario" class="cmxform" method="get" action="">    
                
                <div class="row"> <!-- style="margin-top: 15px!important;" --->
                                <div class='col-md-12'>
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Nombre del Horario:</label> 
                                        <input id="nombre" type="text" class="form-control required" required>
                                    </div>
                                </div>
                            </div>                             
                    <div class="row">
                        <div class="col-md-6">
                            <input id="id" type="text" style="display:none;">                          
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Entrada Desde:</label> 
                                                <div class="input-group clockpicker">                                                   
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input type="text" class="form-control required" value="" id="entradaDesde" required readonly> 
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div>  
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Entrada Hasta:</label> 
                                                <div class="input-group clockpicker">                                                    
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input required type="text" class="form-control required" value="" id="entradaHasta" readonly>
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div>   
                            
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Salida Inicio:</label> 
                                                <div class="input-group clockpicker">                                                   
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input required type="text" class="form-control" value="" id="salidaInicio" readonly> 
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div>    
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Horas:</label> 
                                                <input required type="text" class="form-control" value="" id="horasdiarias" maxlength="2" onkeypress='return validaNumericos(event)' >
                                    </div>
                                </div>
                            </div>  


                            
                        
                            
                            <div class="row"  style="margin-top: 15px!important;display:none;"> <!-- style="margin-top: 15px!important;" --->
                                <div class='col-md-12'>
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Fecha de Asignación:</label> 
                                        <div id="datepicker" class="input-group date" data-date-format="yyyy-mm-dd">
                                            <input id="fechaAsignacion" class="form-control" type="text" readonly />
                                            <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                                        </div>
                                    </div>
                                </div>
                            </div>   
                             
                               



                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Tipo:</label> 
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="custom-control custom-radio">
                                                    <input id="tipoDiurno" name="radio" type="radio" class="custom-control-input" checked="checked">
                                                    <span class="custom-control-label">Diurno</span>
                                                </label>
                                                
                                            </div>
                                        <div class="col-md-6">
                                                <label class="custom-control custom-radio">
                                                    <input id="tipoNocturno" name="radio" type="radio" class="custom-control-input">
                                                    <span class="custom-control-label">Nocturno</span>
                                                </label>
                                                
                                            </div></div>

                                    

                                        </div>
                                </div>
                            </div> 
                                

                        </div>
                        <div class="col-md-6">        
                            
                            
                        <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Entrada Inicio:</label> 
                                                <div class="input-group clockpicker">                                                   
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input required type="text" class="form-control" value="" id="entradaInicio" readonly> 
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div> 
                                
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Salida Desde:</label> 
                                                <div class="input-group clockpicker">                                                   
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input required type="text" class="form-control" value="" id="salidaDesde" readonly> 
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div> 
                            

                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Salida Hasta:</label> 
                                                <div class="input-group clockpicker">                                                    
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input required type="text" class="form-control" value="" id="salidaHasta" readonly>
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div>    
                            

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Tiempo Comida:</label> 
                                                <div class="input-group clockpicker">                                                    
                                                    <span class="input-group-addon">
                                                        <div data-icon="ei-clock" data-size="s"></div>
                                                    </span>
                                                    <input required type="text" class="form-control required" value="" id="tiempoComida" readonly>
                                                </div>
                                                <script type="text/javascript">
                                                            
                                                    $('.clockpicker').clockpicker({
                                                        placement: 'bottom',
                                                        align: 'left',
                                                        donetext: 'Done',
                                                        vibrate: true,
                                                        autoclose: true,
                                                    });
                                                        </script>
                                    </div>
                                </div>
                            </div> 

                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group" style="position: absolute;">
                                        <div class="controls" style="WIDTH: 146%;MARGIN: inherit;"> <!--- style="WIDTH: 146%;MARGIN: inherit;"> -->
                                        <label class="custom-control custom-checkbox">
                                            <input id="comedor" type="checkbox" value="single" name="styled_single_checkbox" 
                                            class="custom-control-input" aria-invalid="false">
                                            <span class="custom-control-label">Sale a Comer</span> </label>
                                            <div class="help-block">

                                            </div>
                                        </div>                                        
                                    </div>
                                </div>
                            </div> 
  


                                                   
                        </div>
                    </div>
                </form>
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

    <script src="assets/js/horarios.js"></script>


<style>
@media only screen and (min-width: 600px) {
    .table-responsive {
        overflow-x: visible;
        -ms-overflow-style: -ms-autohiding-scrollbar;
    }
}
@media only screen and (max-width: 600px) {
    .table-responsive {
        overflow-x: auto;
    }
}
</style>
<script>
function validaNumericos(event) {
    if(event.charCode >= 48 && event.charCode <= 57){
      return true;
     }
     return false;
}
</script>