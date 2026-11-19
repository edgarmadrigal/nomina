<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<style >
</style>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title"><?php echo isset($soloEmpresa) ? 'Checadas ' . htmlspecialchars($soloEmpresa['nombre']) . ' (directo de checadores)' : 'Checadas Directo de Checadores y Biostar'; ?></h4> 
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
                                                <option  value="2020">2020</option>
                                                <option  value="2021">2021</option>
                                                <option    value="2022">2022</option>
                                                <option  value="2023">2023</option>
                                                <option   value="2024">2024</option>
                                                <option   value="2025">2025</option>
                                                <option selected value="2026">2026</option>
                                                <option  value="2027">2027</option>
                                                <option  value="2028">2028</option>
                                                <option  value="2029">2029</option>
                                                <option  value="2030">2030</option>
                                                <option  value="2031">2031</option>
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
                                    <!--
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
-->
<!--
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Empleado</label>
                                            <input id="noempleado" type="text" class="form-control" name="msg" placeholder="escriba NoEmpleado">
                                        </div>
                                    </div>


-->

                                    <div class="col-2">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="buscar" class="btn btn-info">
                                                        <i class="fa fa-search"></i> Buscar
                                                    </button>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="procesarSemana" class="btn btn-warning">
                                                        <i class="fa fa-refresh"></i> Procesar
                                                    </button>
                                        </div>
                                    </div>
                                    <?php if (!empty($esTorreon)) { ?>
                                    <div class="col-2">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br>
                                                    <button id="descargarReloj" class="btn btn-success">
                                                        <i class="fa fa-download"></i> Descargar del reloj
                                                    </button>
                                        </div>
                                    </div>
                                    <?php } ?>
									<!--
                                    <div class="col-2" >
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                            <button id="actualizarNomi" class="btn btn-warning" style="display:none;">
                                                <i class="fa fa-refresh"></i> Actualizar de Nomiplus</button>
                                            </button>
                                        </div>
                                    </div>
-->
                                </div>
                                <?php if (!empty($esTorreon)) { ?>
                                <div class="row">
                                    <div class="col-12">
                                        <div id="resumenRelojes" style="display:flex; flex-wrap:wrap; gap:6px; margin-bottom:6px;"></div>
                                    </div>
                                </div>
                                <?php } ?>
                                <div class="row">
                                    <div class="col-12">
                                        <div id="infoUltimoProceso" style="margin-top:5px;font-style:italic;color:#555;"></div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12" style="text-align: -webkit-center;">
                                        <center>
                                        <div class="loadTable" style="display:none;"></div>                                        
                                        </center>
                                    </div>
                                </div>
                                <div class="row cb-columnas" style="margin-bottom:8px;">
                                    <div class="col-auto"><label style="font-weight:bold;">Mostrar columnas</label></div>
                                    <div class="col-auto"><label class="cb-col"><input type="checkbox" id="cbColComida"> Checadas de comida</label></div>
                                    <div class="col-auto"><label class="cb-col"><input type="checkbox" id="cbColEmpresa" checked> Empresa</label></div>
                                    <div class="col-auto"><label class="cb-col"><input type="checkbox" id="cbColSemana" checked> No. semana</label></div>
                                    <div class="col-auto"><label class="cb-col"><input type="checkbox" id="cbColFechas"> Fechas (inicio y fin)</label></div>
                                    <div class="col-auto"><label class="cb-col"><input type="checkbox" id="cbColDepto"> Departamento</label></div>
                                    <div class="col-auto"><label class="cb-col"><input type="checkbox" id="cbColPuesto"> Puesto</label></div>
                                </div>
                                <div class="row" id="cbFiltroIncidencias" style="display:none; margin-bottom:8px;">
                                    <div class="col-auto">
                                        <label style="font-weight:bold;">Filtrar por incidencia</label>
                                    </div>
                                    <div class="col-auto">
                                        <select class="custom-select custom-select-sm" id="cbFiltroDia">
                                            <option value="">Cualquier día</option>
                                            <option value="Lunes">Lunes</option>
                                            <option value="Martes">Martes</option>
                                            <option value="Miercoles">Miércoles</option>
                                            <option value="Jueves">Jueves</option>
                                            <option value="Viernes">Viernes</option>
                                            <option value="Sabado">Sábado</option>
                                            <option value="Domingo">Domingo</option>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <select class="custom-select custom-select-sm" id="cbFiltroInc">
                                            <option value="">Todas</option>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-sm" id="cbFiltroLimpiar" style="display:none; background:#e53935; color:#fff; font-weight:600;"><i class="fa fa-times"></i> Quitar filtro</button>
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
                                                    <th>Empresa</th>
                                                    <th>NoSemana</th>
                                                    <th>FechaInicio</th>
                                                    <th>FechaFin</th>
                                                    <th>Departamento</th>
                                                    <th>Puesto</th>

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
                                                    <th>Empresa</th>
                                                    <th>NoSemana</th>
                                                    <th>FechaInicio</th>
                                                    <th>FechaFin</th>
                                                    <th>Departamento</th>
                                                    <th>Puesto</th>

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

<!-- Modal: checadas de la última descarga de un reloj -->
<div class="modal fade" id="modalBajadasReloj" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBajadasRelojTitulo">Última descarga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detalleBajadasReloj"></div>
                <div class="row align-items-end">
                    <div class="col-6 form-group">
                        <input type="text" class="form-control" id="bajadasRelojBuscar" placeholder="Buscar número o nombre">
                    </div>
                    <div class="col-6 form-group text-right">
                        <strong id="resumenBajadasReloj"></strong>
                    </div>
                </div>
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Número</th>
                                <th>Nombre</th>
                                <th>Planta</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoBajadasReloj"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a href="controllerRelojes" class="btn btn-info mr-auto"><i class="fa fa-clock-o"></i> Ir al catálogo de relojes</a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>var SOLO_EMPRESA = <?php echo isset($soloEmpresa) ? json_encode($soloEmpresa) : 'null'; ?>;   // reporte fijo de una empresa (Checadas IBF)</script>
<style>
.cb-columnas .cb-col { font-weight: normal; cursor: pointer; margin: 0; }
/* El tema oculta los checkbox sueltos (left:-9999px); aquí se usan los nativos */
.cb-columnas .cb-col input[type="checkbox"] {
    position: static !important; left: auto !important; opacity: 1 !important; visibility: visible !important;
    width: 16px; height: 16px; margin: 0 6px 0 0; vertical-align: middle; cursor: pointer; accent-color: #1e88e5;
}
</style>
<script src="assets/js/jquery.blockUI.js"></script>
<script src="assets/js/checadaBiostar.js?v=<?php echo filemtime(FCPATH.'assets/js/checadaBiostar.js'); ?>"></script>
<style>
.chk-falta { display: inline-block; background: #e53935; color: #fff !important; font-weight: bold; padding: 1px 7px; border-radius: 3px; font-size: 11px; }
.chk-vac-chk { display: inline-block; background: #fb8c00; color: #fff !important; font-weight: bold; padding: 1px 7px; border-radius: 3px; font-size: 11px; white-space: nowrap; cursor: pointer; text-decoration: none !important; }
a.chk-vac-chk:hover { background: #ef6c00; color: #fff !important; }
    
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
/* Celular: número, nombre y checadas compactos para que quepan más días en la pantalla */
@media only screen and (max-width: 767px) {
    #example { font-size: 11px; }
    #example th, #example td { padding: 3px 4px !important; }
    #example thead tr:first-child td { font-size: 12px !important; padding: 2px !important; }
    #example th:nth-child(1), #example td:nth-child(1) { width: 46px; min-width: 46px; max-width: 46px; }
    #example th:nth-child(2), #example td:nth-child(2) {
        width: 105px; min-width: 105px; max-width: 105px;
        white-space: normal; font-size: 10px; line-height: 1.15; word-break: break-word;
    }
    #example thead th:nth-child(2), #example thead td:nth-child(2), #example tbody td:nth-child(2),
    #example tfoot th:nth-child(2), #example tfoot td:nth-child(2) { left: 46px; }
    #example thead th input { width: 100%; min-width: 0; font-size: 10px; padding: 1px 2px; }
    #example tfoot { display: none; }
    #example .chk-falta, #example .chk-vac-chk { padding: 0 3px; font-size: 9px; }
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
</style>
