
<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>
<script src="assets/js/jquery.blockUI.js"></script>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Reporte Asistencia Txt</h4> 
                <div class="card">
                    <div class="card-body">     
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
                                        <option value="2023">2023</option>
                                        <option value="2024">2024</option>
                                        <option value="2025">2025</option>
                                        <option selected value="2026">2026</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-2" >
                                <div class="form-group">
                                    <label style="font-weight: bold!important;">Planta</label>
                                     <select class="custom-select col-12" id="planta" required></select>
                                </div>
                            </div>
                            
                            <div class="col-2" >
                                <div class="form-group">
                                    <br>
                                    <label class="custom-control custom-checkbox  col-12" style="font-weight: bold!important;">
                                        <input id="checkTiempoExtra" type="checkbox" value="single" name="styled_single_checkbox" class="custom-control-input" aria-invalid="false">
                                    <span class="custom-control-label">Tiempo Extra</span> </label>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                    <button id="descargar" class="btn btn-info">
                                        <i class="fa fa-download"></i> Descargar fichero
                                    </button>
                                    <a href="assets/formatos/Excepciones%20Nomina%20SISAI.txt" download="Excepciones Nomina SISAI.txt" class="d-block mt-1" style="font-size:12px;"
                                       title="Formato de importación de Microsip (Excepciones de la nómina) que corresponde a este TXT"><i class="fa fa-file-text-o"></i> Formato para Microsip</a>
                                </div>
                            </div>
                            
                            <div class="col-2">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                        <button id="actualizar" class="btn btn-success">
                                            Procesar  <i class="fa fa-refresh"></i>
                                        </button>
                                </div>
                            </div>
                        </div> 
<div class="row" id="filaDiasTE" style="display:none;">
    <div class="col-12">
        <div class="form-group">
            <label style="font-weight: bold!important; margin-right:10px;">Días con tiempo extra:</label>

            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE1" checked>
                <span class="custom-control-label">Lunes</span>
            </label>
            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE2" checked>
                <span class="custom-control-label">Martes</span>
            </label>
            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE3" checked>
                <span class="custom-control-label">Miércoles</span>
            </label>
            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE4" checked>
                <span class="custom-control-label">Jueves</span>
            </label>
            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE5" checked>
                <span class="custom-control-label">Viernes</span>
            </label>
            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE6" checked>
                <span class="custom-control-label">Sábado</span>
            </label>
            <label class="custom-control custom-checkbox custom-control-inline">
                <input type="checkbox" class="custom-control-input diaTE" id="diaTE7" checked>
                <span class="custom-control-label">Domingo</span>
            </label>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJUSTE 2026-11-19: aviso del formato nuevo (DIAS_VACACIONES y TIPO_MOVIMIENTO) antes de descargar el TXT -->
<div class="modal fade" id="modalFormatoTxt" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff3cd;">
                <h5 class="modal-title"><i class="fa fa-exclamation-triangle" style="color:#d39e00;"></i> El TXT para Microsip cambió</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="font-size:14px;">
                <p>Desde ahora el TXT trae <b>dos datos más</b> al final de cada empleado:</p>
                <ul>
                    <li><b>Días de vacaciones</b>: ya no se suman a los días a pagar; van en su propio campo.</li>
                    <li><b>Tipo de movimiento</b>: el TXT <b>reemplaza</b> la excepción del empleado en Microsip.</li>
                </ul>
                <p class="mb-2">Antes de importar en Microsip tienes que usar el <b>formato nuevo</b>. Con el formato anterior la importación marca error.</p>
                <a href="assets/formatos/Excepciones%20Nomina%20SISAI.txt" download="Excepciones Nomina SISAI.txt" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-file-text-o"></i> Descargar formato nuevo para Microsip
                </a>
                <div class="custom-control custom-checkbox mt-3">
                    <input type="checkbox" class="custom-control-input" id="cbFormatoNuevo">
                    <label class="custom-control-label" for="cbFormatoNuevo">Ya tengo el formato nuevo en Microsip</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btnDescargarTxtOk" disabled><i class="fa fa-download"></i> Descargar TXT</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/reporteAsistenciatxt.js"></script>
