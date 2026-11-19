<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Respaldos</h4>

                <!-- Estado general -->
                <div class="card">
                    <div class="card-body" id="estadoRespaldos">
                        <i class="fa fa-spinner fa-spin"></i> Cargando...
                    </div>
                </div>

                <!-- Configuración por tipo -->
                <div class="row" id="tarjetasRespaldo"></div>

                <!-- Historial -->
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-3 form-group">
                                <label style="font-weight: bold!important;">Tipo</label>
                                <select class="custom-select col-12" id="filtroTipo">
                                    <option value="">Todos</option>
                                    <option value="BASE">Base de datos completa</option>
                                    <option value="LOG">Log de transacciones</option>
                                    <option value="CHECADAS">Checadas</option>
                                    <option value="CODIGO">Código del sistema</option>
                                    <option value="ROSTROS">Rostros de los relojes</option>
                                </select>
                            </div>
                            <div class="col-3 form-group">
                                <label style="font-weight: bold!important;">Periodo</label>
                                <select class="custom-select col-12" id="filtroDias">
                                    <option value="1">Último día</option>
                                    <option value="7" selected>Últimos 7 días</option>
                                    <option value="30">Últimos 30 días</option>
                                    <option value="90">Últimos 90 días</option>
                                </select>
                            </div>
                            <div class="col-6 form-group text-right">
                                <button class="btn btn-info" id="btnRefrescar"><i class="fa fa-refresh"></i> Actualizar</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Inicio</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Duración</th>
                                        <th>Tamaño</th>
                                        <th>Archivo</th>
                                        <th>Origen</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoHistorial"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ubicación de los respaldos -->
<div class="modal fade" id="modalUbicacion" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-folder"></i> Ubicación de los respaldos</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label style="font-weight: bold!important;">Carpeta principal</label>
                    <label class="custom-control custom-checkbox mb-1">
                        <input type="checkbox" class="custom-control-input" id="ubiUsarPred">
                        <span class="custom-control-label">Usar la carpeta predeterminada: <span id="ubiPredeterminada" class="text-muted" style="word-break: break-all;"></span></span>
                    </label>
                    <input type="text" class="form-control" id="ubiRuta1" maxlength="250" placeholder="Ej. D:\Respaldos\Nominas">
                </div>
                <div class="form-group">
                    <label class="d-block w-100" style="font-weight: bold!important; float: none !important;">Copias adicionales <small class="text-muted">(hasta 3: otra carpeta, red o nubes)</small></label>
                    <div id="ubiCopias" style="clear: both;"></div>
                    <button type="button" class="btn btn-sm btn-info" id="ubiAgregar" style="color:#fff !important;"><i class="fa fa-plus"></i> Agregar copia</button>
                </div>
                <div class="alert alert-secondary" style="font-size:12px;">
                    <b><i class="fa fa-cloud"></i> MEGA, Google Drive, OneDrive o Dropbox:</b> instala en el servidor la aplicación de esa nube
                    (MEGA: <i>MEGA Desktop App</i>), inicia sesión y elige la carpeta que sincroniza; aquí escribe esa carpeta
                    (ej. <code>C:\Users\Administrador\MEGA\Respaldos</code>). Los respaldos se guardan ahí y la aplicación los sube sola.
                    La aplicación debe quedar abierta en el servidor y la cuenta de SQL Server necesita permiso de escritura en esa carpeta.
                    <b>Azure</b> se conecta directo, sin aplicación.
                </div>
                <div class="alert alert-info mb-0" style="font-size:13px;">
                    <i class="fa fa-info-circle"></i> Las rutas son del <b>servidor de SQL Server</b> (AIN-MSSRV2), no de esta computadora.
                    Se recomienda tener copias en <b>otro disco, otro equipo o en la nube</b>, para no perder los respaldos si falla el servidor.
                    Al guardar se prueba que SQL Server pueda escribir en la carpeta; en una carpeta de red hay que dar permiso de escritura al equipo <b>AIN-MSSRV2$</b>.
                    Los respaldos que ya existen se quedan en la carpeta anterior.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="btnGuardarUbicacion"><i class="fa fa-check"></i> Probar y guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/respaldos.js?v=<?php echo filemtime(FCPATH.'assets/js/respaldos.js'); ?>"></script>
