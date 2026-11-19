<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Reporte Checadas Nomiplus</h4> 
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
                                                <option value="2018">2018</option>
                                                <option  value="2019">2019</option>
                                                <option value="2020">2020</option>
                                                <option  value="2021">2021</option>
                                                <option  value="2022">2022</option>
                                                <option  value="2023">2023</option>
                                                <option   value="2024">2024</option>
                                                <option   value="2025">2025</option> 
                                                <option  selected value="2026">2026</option>
                                                <option  value="2027">2027</option>
                                                <option  value="2028">2028</option>
                                                <option  value="2029">2029</option>
                                                <option  value="2030">2030</option>
                                                <option  value="2031">2031</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Departamento</label>
                                            <select class="custom-select col-12" id="departamento">
                                            </select>
                                        </div>
                                    </div> 
                                    <div class="col-2">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Empleado</label>
                                            <input id="noempleado" type="text" class="form-control" name="msg" placeholder="escriba NoEmpleado">
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="buscar" class="btn btn-danger">
                                                        <i class="fa fa-search"></i> Buscar
                                                    </button>
                                        </div>
                                    </div>
                                    <div class="col-1">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="reprocesar" class="btn btn-warning">
                                                        <i class="fa fa-refresh"></i>ReProcesar
                                                    </button>
                                        </div>
                                    </div> &nbsp; 
                                    <div class="col-1 " style="margin-left: 15px;">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="act" class="btn btn-info">
                                                    <i class="fa fa-search"></i>ACTIVOS/INACTIVOS
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
                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Lunes</td>
                                                    
                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Martes</td>
                                                    
                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Miercoles</td>
                                                    
                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Jueves</td>
                                                    
                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Viernes</td>
                                                    
                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Sabado</td>

                                                    <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Domingo</td>
                                                
                                                </tr>
                                                <tr>
                                                    <th>Numero</th>
                                                    <th>NombreCompleto</th>
                                                    <th>FechaInicio</th>
                                                    <th>FechaFin</th>     

                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th >Lunes</th>
                                                    <th>L.Entrada</th>
                                                    <th>L.EntradaComedor</th>
                                                    <th>L.SalidaComedor</th>
                                                    <th>L.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th >Martes</th>
                                                    <th>MA.Entrada</th>
                                                    <th>MA.EntradaComedor</th>
                                                    <th>MA.SalidaComedor</th>
                                                    <th>MA.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th >Miercoles</th>
                                                    <th>MI.Entrada</th>
                                                    <th>MI.EntradaComedor</th>
                                                    <th>MI.SalidaComedor</th>
                                                    <th>MI.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Jueves</th>
                                                    <th>J.Entrada</th>
                                                    <th>J.EntradaComedor</th>
                                                    <th>J.SalidaComedor</th>
                                                    <th>J.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Viernes</th>
                                                    <th>V.Entrada</th>
                                                    <th>V.EntradaComedor</th>
                                                    <th>V.SalidaComedor</th>
                                                    <th>V.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Sabado</th>
                                                    <th>S.Entrada</th>
                                                    <th>S.EntradaComedor</th>
                                                    <th>S.SalidaComedor</th>
                                                    <th>S.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Domingo</th>
                                                    <th>D.Entrada</th>
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

                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th >Lunes</th>
                                                    <th>L.Entrada</th>
                                                    <th>L.EntradaComedor</th>
                                                    <th>L.SalidaComedor</th>
                                                    <th>L.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th >Martes</th>
                                                    <th>MA.Entrada</th>
                                                    <th>MA.EntradaComedor</th>
                                                    <th>MA.SalidaComedor</th>
                                                    <th>MA.Salida</th>

                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th >Miercoles</th>
                                                    <th>MI.Entrada</th>
                                                    <th>MI.EntradaComedor</th>
                                                    <th>MI.SalidaComedor</th>
                                                    <th>MI.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Jueves</th>
                                                    <th>J.Entrada</th>
                                                    <th>J.EntradaComedor</th>
                                                    <th>J.SalidaComedor</th>
                                                    <th>J.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Viernes</th>
                                                    <th>V.Entrada</th>
                                                    <th>V.EntradaComedor</th>
                                                    <th>V.SalidaComedor</th>
                                                    <th>V.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Sabado</th>
                                                    <th>S.Entrada</th>
                                                    <th>S.EntradaComedor</th>
                                                    <th>S.SalidaComedor</th>
                                                    <th>S.Salida</th>
                                                    
                                                    <th style="border-left: inset!important;border-color: black;"></th>
                                                    <th>Domingo</th>
                                                    <th>D.Entrada</th>
                                                    <th>D.EntradaComedor</th>
                                                    <th>D.SalidaComedor</th>
                                                    <th>D.Salida</th>
                                                </tr>
                                                
                                                <tr>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td></td>
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Lunes</td>
                                                        
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Martes</td>
                                                        
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Miercoles</td>
                                                        
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Jueves</td>
                                                        
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Viernes</td>
                                                        
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Sabado</td>
    
                                                        <td COLSPAN=6 style="border-style: groove;border-width: 2px;text-align: center;font-size:large;font-weight:bold;color:black;border-color: black;" >Domingo</td>
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
<script src="assets/js/checada.js"></script>
<style>
	/* Contenedor con scroll */
.table-responsive {
    overflow-x: auto;
    position: relative;
}

/* Anchos fijos para poder calcular el "left" de la 2da columna */
#example th:nth-child(1),
#example td:nth-child(1) {
    width: 70px;
    min-width: 70px;
    max-width: 70px;
}
#example th:nth-child(2),
#example td:nth-child(2) {
    width: 220px;
    min-width: 220px;
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Columna 1 fija: Numero */
#example thead th:nth-child(1),
#example thead td:nth-child(1),
#example tbody td:nth-child(1),
#example tfoot th:nth-child(1),
#example tfoot td:nth-child(1) {
    position: sticky;
    left: 0;
    z-index: 2;
    background-color: #fff;
}

/* Columna 2 fija: NombreCompleto (left = ancho de la columna 1) */
#example thead th:nth-child(2),
#example thead td:nth-child(2),
#example tbody td:nth-child(2),
#example tfoot th:nth-child(2),
#example tfoot td:nth-child(2) {
    position: sticky;
    left: 70px;
    z-index: 2;
    background-color: #fff;
}

/* El thead siempre por encima de las columnas fijas */
#example thead th {
    z-index: 3;
}

/* Respetar el rayado de table-striped en las celdas fijas */
#example.table-striped tbody tr:nth-of-type(odd) td:nth-child(1),
#example.table-striped tbody tr:nth-of-type(odd) td:nth-child(2) {
    background-color: rgba(0,0,0,.05);
}

/* Sombra sutil para marcar visualmente el borde de las columnas fijas */
#example td:nth-child(2),
#example th:nth-child(2) {
    box-shadow: 2px 0 3px -1px rgba(0,0,0,.15);
}
#example.table-striped tbody tr:nth-of-type(odd) td:nth-child(1), #example.table-striped tbody tr:nth-of-type(odd) td:nth-child(2) {
    background-color: #fff;
}

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
.btn {
    padding: 7px 2px!important;
    cursor: pointer;
}
</style>
