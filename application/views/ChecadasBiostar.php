<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<style >
</style>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Checadas</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">   
                                <div class="row">
                                    <div class="col-2" >
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Semana</label>
                                            <input id="NoSemana" type="text" class="form-control" name="msg" placeholder="escriba NoSemana" >
                                        </div>
                                    </div>                                    
                                    <div class="col-2" >
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Año</label>
                                            <select class="custom-select col-12" id="anio">
                                                <option value="2019">2018</option>
                                                <option  value="2019">2019</option>
                                                <option selected value="2020">2020</option>
                                                <option value="2021">2021</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta</label>
                                            <select class="custom-select col-12" id="planta">
                                            </select>
                                        </div>
                                    </div> 
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Departamento</label>
                                            <select class="custom-select col-12" id="departamento">
                                            </select>
                                        </div>
                                    </div> 
                                </div> 
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Puesto</label>
                                            <select class="custom-select col-12" id="puesto">
                                            </select>
                                        </div>
                                    </div> 

                                    <div class="col-3">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Empleado</label>
                                            <input id="noempleado" type="text" class="form-control" name="msg" placeholder="escriba NoEmpleado">
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="buscar" class="btn btn-info">
                                                        <i class="fa fa-search"></i> Buscar
                                                    </button>
                                        </div>
                                    </div>
                                    <div class="col-2" >
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                            <button id="actualizarNomi" class="btn btn-warning" style="display:none;">
                                                <i class="fa fa-refresh"></i> Actualizar de Nomiplus</button>
                                            </button>
                                        </div>
                                    </div>
                                </div> 
                                <div class="row">
                                    <div class="col-12" style="text-align: -webkit-center;">
                                        <center>
                                        <div class="loadTable" style="display:none;"></div>                                        
                                        </center>
                                    </div>
                                </div>
                                <div class="row" id="tablaEmp" >
                                    <div class="col-12">
                                        <table id="example" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Lunes</td>                                                    
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Martes</td>                                                    
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Miercoles</td>                                                    
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Jueves</td>                                                    
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Viernes</td>                                                    
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Sabado</td>
                                                    <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Domingo</td>
                                                </tr>
                                                <tr>
                                                    <th>Numero</th>
                                                    <th>NombreCompleto</th>
                                                    <th>FechaInicio</th>
                                                    <th>FechaFin</th>

                                                    <th style="border-left: inset!important;border-color: black;">L.Entrada</th>
                                                    <th>L.EntradaComedor</th>
                                                    <th>L.SalidaComedor</th>
                                                    <th>L.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;">MA.Entrada</th>
                                                    <th>MA.EntradaComedor</th>
                                                    <th>MA.SalidaComedor</th>
                                                    <th>MA.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;">MI.Entrada</th>
                                                    <th>MI.EntradaComedor</th>
                                                    <th>MI.SalidaComedor</th>
                                                    <th>MI.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">J.Entrada</th>
                                                    <th>J.EntradaComedor</th>
                                                    <th>J.SalidaComedor</th>
                                                    <th>J.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">V.Entrada</th>
                                                    <th>V.EntradaComedor</th>
                                                    <th>V.SalidaComedor</th>
                                                    <th>V.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">S.Entrada</th>
                                                    <th>S.EntradaComedor</th>
                                                    <th>S.SalidaComedor</th>
                                                    <th>S.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">D.Entrada</th>
                                                    <th>D.EntradaComedor</th>
                                                    <th>D.SalidaComedor</th>
                                                    <th>D.Salida</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                
                                            <tr>
                                                    <th>Numero</th>
                                                    <th>NombreCompleto</th>
                                                    <th>FechaInicio</th>
                                                    <th>FechaFin</th>     

                                                    <th style="border-left: inset!important;border-color: black;">L.Entrada</th>
                                                    <th>L.EntradaComedor</th>
                                                    <th>L.SalidaComedor</th>
                                                    <th>L.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;">MA.Entrada</th>
                                                    <th>MA.EntradaComedor</th>
                                                    <th>MA.SalidaComedor</th>
                                                    <th>MA.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;">MI.Entrada</th>
                                                    <th>MI.EntradaComedor</th>
                                                    <th>MI.SalidaComedor</th>
                                                    <th>MI.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">J.Entrada</th>
                                                    <th>J.EntradaComedor</th>
                                                    <th>J.SalidaComedor</th>
                                                    <th>J.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">V.Entrada</th>
                                                    <th>V.EntradaComedor</th>
                                                    <th>V.SalidaComedor</th>
                                                    <th>V.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">S.Entrada</th>
                                                    <th>S.EntradaComedor</th>
                                                    <th>S.SalidaComedor</th>
                                                    <th>S.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;">D.Entrada</th>
                                                    <th>D.EntradaComedor</th>
                                                    <th>D.SalidaComedor</th>
                                                    <th>D.Salida</th>
                                                </tr>
                                                
                                                <tr>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Lunes</td>
                                                        
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Martes</td>
                                                        
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Miercoles</td>
                                                        
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Jueves</td>
                                                        
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Viernes</td>
                                                        
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Sabado</td>
    
                                                        <td COLSPAN=4 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Domingo</td>
                                                    
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

<script src="assets/js/jquery.blockUI.js"></script>
<script src="assets/js/checadaBiostar.js"></script>
<style>

@media only screen and (min-width: 1000px) {
    .table-responsive {
        overflow-x: visible;
        -ms-overflow-style: -ms-autohiding-scrollbar;
    }
}
    .table-responsive {
        overflow-x: auto;
    }
.loadTable {
  border: 16px solid #f3f3f3;
  border-radius: 50%;
  border-top: 16px solid #3498db;
  width: 120px;
  height: 120px;
  -webkit-animation: spin 2s linear infinite; /* Safari */
  animation: spin 2s linear infinite;
}
/* Safari */
@-webkit-keyframes spin {
  0% { -webkit-transform: rotate(0deg); }
  100% { -webkit-transform: rotate(360deg); }
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>