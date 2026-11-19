<div class="page-wrapper">
    <div class="container-fluid" id="bitacora">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title"><i class="fa fa-history"></i> Historial de cambios</h4>
                <p class="text-muted bit-ayuda">Quién dio de alta, modificó o eliminó algo en los catálogos, con fecha y hora. Se registra solo
                    (también los procesos automáticos y los cambios hechos directo en la base de datos).</p>

                <!-- Hoy -->
                <div class="row" id="bitHoy">
                    <div class="col-6 col-md-3"><div class="card bit-kpi bit-alta"><div class="card-body"><small>Altas hoy</small><b data-k="altas">–</b></div></div></div>
                    <div class="col-6 col-md-3"><div class="card bit-kpi bit-mod"><div class="card-body"><small>Modificaciones hoy</small><b data-k="modificaciones">–</b></div></div></div>
                    <div class="col-6 col-md-3"><div class="card bit-kpi bit-elim"><div class="card-body"><small>Eliminaciones hoy</small><b data-k="eliminaciones">–</b></div></div></div>
                    <div class="col-6 col-md-3"><div class="card bit-kpi"><div class="card-body"><small>Quién cambió más hoy</small><div id="bitTopUsuarios" class="bit-top">–</div></div></div></div>
                </div>

                <!-- En tiempo real -->
                <div class="card">
                    <div class="card-body">
                        <div class="bit-titulo">
                            <h5 class="mb-0"><span class="bit-punto"></span> Últimos cambios <small class="text-muted">en tiempo real</small></h5>
                            <div>
                                <label class="mr-2 mb-0" style="font-weight:600; cursor:pointer;" title="Oculta los cambios hechos directo en SQL y por procesos automáticos; deja solo los que hizo una persona">
                                    <input type="checkbox" id="bitSoloUsuarios" checked> Solo usuarios
                                </label>
                                <span id="bitEstadoVivo" class="text-muted"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="bitPausa"><i class="fa fa-pause"></i> Pausar</button>
                            </div>
                        </div>
                        <div class="table-responsive bit-vivo">
                            <table class="table table-sm table-hover bit-tabla">
                                <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Acción</th><th>Catálogo</th><th>Registro</th><th>Qué cambió</th><th></th></tr></thead>
                                <tbody id="bitVivo"><tr><td colspan="7" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando...</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Historial -->
                <div class="card">
                    <div class="card-body">
                        <h5>Historial</h5>
                        <div class="row align-items-end bit-filtros">
                            <div class="col-6 col-md-2 form-group"><label>Desde</label><input type="date" class="form-control form-control-sm" id="bitDesde"></div>
                            <div class="col-6 col-md-2 form-group"><label>Hasta</label><input type="date" class="form-control form-control-sm" id="bitHasta"></div>
                            <div class="col-6 col-md-2 form-group"><label>Catálogo</label>
                                <select class="form-control form-control-sm" id="bitTabla"><option value="">Todos</option>
                                    <?php foreach ($catalogos as $t => $n): ?><option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($n); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="col-6 col-md-2 form-group"><label>Acción</label>
                                <select class="form-control form-control-sm" id="bitAccion"><option value="">Todas</option>
                                    <option value="ALTA">Altas</option><option value="MODIFICACION">Modificaciones</option><option value="ELIMINACION">Eliminaciones</option>
                                </select></div>
                            <div class="col-6 col-md-2 form-group"><label>Usuario</label><input type="text" class="form-control form-control-sm" id="bitUsuario" placeholder="ej. EDGAR"></div>
                            <div class="col-6 col-md-2 form-group"><label>Buscar</label><input type="text" class="form-control form-control-sm" id="bitTexto" placeholder="no. empleado, nombre..."></div>
                            <div class="col-12 form-group text-right">
                                <button type="button" class="btn btn-sm btn-info" id="bitBuscar"><i class="fa fa-search"></i> Buscar</button>
                                <button type="button" class="btn btn-sm btn-success" id="bitExcel"><i class="fa fa-file-excel-o"></i> Exportar</button>
                            </div>
                        </div>
                        <div id="bitCuenta" class="text-muted mb-2"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover bit-tabla">
                                <thead><tr>
                                    <th>Fecha y hora <span class="bit-flt" data-col="fecha" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th>Usuario <span class="bit-flt" data-col="usuario" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th>Acción <span class="bit-flt" data-col="accion" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th>Catálogo <span class="bit-flt" data-col="catalogo" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th>Registro <span class="bit-flt" data-col="registro" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th>Qué cambió <span class="bit-flt" data-col="cambios" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th>Equipo / pantalla <span class="bit-flt" data-col="origen" title="Filtrar"><i class="fa fa-filter"></i></span></th>
                                    <th></th>
                                </tr></thead>
                                <tbody id="bitHistorial"></tbody>
                            </table>
                        </div>
                        <div id="bitFltPanel" class="bit-fltpanel" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detalle de un movimiento / historia de un registro -->
<div class="modal fade" id="modalBitacora" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bitModalTitulo">Detalle</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="bitModalCuerpo"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-warning" id="bitRevertir" style="display:none;"><i class="fa fa-undo"></i> <span>Revertir</span></button>
                <button type="button" class="btn btn-sm btn-outline-warning" id="bitReaplicar" style="display:none;"><i class="fa fa-repeat"></i> <span>Reaplicar</span></button>
                <button type="button" class="btn btn-sm btn-info" id="bitVerRegistro"><i class="fa fa-list"></i> Toda la historia de este registro</button>
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
#bitacora .bit-ayuda { font-size: 13px; }
#bitacora .bit-kpi .card-body { padding: 12px 16px; }
#bitacora .bit-kpi small { display: block; color: #8a96a3; }
#bitacora .bit-kpi b { font-size: 26px; }
#bitacora .bit-alta b { color: #2e7d32; } #bitacora .bit-mod b { color: #1e88e5; } #bitacora .bit-elim b { color: #e53935; }
#bitacora .bit-top { font-size: 12px; line-height: 1.5; }
#bitacora .bit-top b { font-size: 13px; }
#bitacora .bit-titulo { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 10px; }
#bitacora .bit-punto { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #43a047; margin-right: 4px; animation: bitLatido 1.6s infinite; }
#bitacora.pausado .bit-punto { background: #9e9e9e; animation: none; }
@keyframes bitLatido { 0%, 100% { opacity: 1; } 50% { opacity: .25; } }
#bitacora .bit-vivo { max-height: 430px; overflow-y: auto; }
#bitacora .bit-tabla td { font-size: 12.5px; vertical-align: top; }
#bitacora .bit-tabla td:first-child { white-space: nowrap; }
#bitacora .bit-tabla .badge { color: #fff !important; font-size: 11px; }
#bitacora tr.bit-nuevo td { animation: bitNuevo 3s ease-out; }
@keyframes bitNuevo { from { background: rgba(255, 178, 43, .45); } to { background: transparent; } }
#bitacora .bit-cambio { white-space: nowrap; }
#bitacora .bit-cambio b { font-weight: 600; }
#bitacora .bit-antes { color: #e53935; text-decoration: line-through; }
#bitacora .bit-despues { color: #2e7d32; }
#bitacora .bit-origen { color: #8a96a3; font-size: 11px; }
#bitacora .bit-mas { color: #8a96a3; font-size: 11px; }
#modalBitacora .bit-campos td { font-size: 12.5px; word-break: break-word; }
#modalBitacora .bit-campos tr.cambio td { background: rgba(255, 178, 43, .15); font-weight: 600; }
html.modo-nocturno #bitacora .bit-antes { color: #ef9a9a; } html.modo-nocturno #bitacora .bit-despues { color: #a5d6a7; }
html.modo-nocturno #modalBitacora .modal-content { background: #23272e; color: #dfe3e8; }
/* En modo oscuro las celdas de la tabla heredaban el texto oscuro de Bootstrap: se fuerzan claras. */
html.modo-nocturno #modalBitacora .bit-campos th,
html.modo-nocturno #modalBitacora .bit-campos td { color: #e8ecf1; border-color: #3a4150; }
html.modo-nocturno #modalBitacora .bit-campos thead th { color: #ffffff; border-bottom-color: #4a515e; }
html.modo-nocturno #modalBitacora .bit-campos tr.cambio td { color: #ffe0b2; }
html.modo-nocturno #modalBitacora .modal-body,
html.modo-nocturno #modalBitacora .modal-body b { color: #e8ecf1; }
html.modo-nocturno #modalBitacora .modal-body .text-muted { color: #9aa4b0 !important; }
html.modo-nocturno #modalBitacora .modal-header { border-bottom-color: #3a4150; }
html.modo-nocturno #modalBitacora .close { color: #dfe3e8; text-shadow: none; opacity: .85; }

/* Filtros tipo Excel por columna */
#bitacora .bit-flt { cursor: pointer; margin-left: 4px; opacity: .45; font-size: 11px; }
#bitacora .bit-flt:hover { opacity: .9; }
#bitacora .bit-flt.activo { opacity: 1; color: #17a2b8; }
.bit-fltpanel { position: absolute; z-index: 1080; background: #fff; border: 1px solid #c3ccd6; border-radius: 4px;
    box-shadow: 0 4px 16px rgba(0,0,0,.18); width: 260px; padding: 8px; font-size: 13px; }
.bit-fltpanel input[type="search"], .bit-fltpanel .bit-flt-search { width: 100%; box-sizing: border-box; margin-bottom: 6px; padding: 3px 6px; }
.bit-fltpanel .bit-flt-lista { max-height: 240px; overflow-y: auto; border: 1px solid #e3e8ee; border-radius: 3px; padding: 4px 6px; }
.bit-fltpanel label { display: block; margin: 0; padding: 2px 0; font-weight: normal; cursor: pointer; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bit-fltpanel label input { margin-right: 6px; }
/* El tema oculta los checkbox sueltos (left:-9999px); aquí se usan los nativos */
.bit-fltpanel input[type="checkbox"] { position: static !important; left: auto !important; opacity: 1 !important;
    visibility: visible !important; width: 14px; height: 14px; margin: 0 6px 0 0; vertical-align: middle; cursor: pointer; accent-color: #17a2b8; }
.bit-fltpanel .bit-flt-acc { display: flex; justify-content: space-between; margin-top: 7px; }
html.modo-nocturno .bit-fltpanel { background: #23272e; border-color: #3a4150; color: #dfe3e8; }
html.modo-nocturno .bit-fltpanel .bit-flt-lista { border-color: #3a4150; }
html.modo-nocturno .bit-fltpanel input { background: #1b1f25; border: 1px solid #3a4150; color: #dfe3e8; }
</style>

<script>var BIT_ADMIN = <?php echo (isset($user->idPerfil) && $user->idPerfil == 1) ? 'true' : 'false'; ?>;</script>
<script src="assets/js/bitacora.js?v=<?php echo filemtime(FCPATH.'assets/js/bitacora.js'); ?>"></script>
