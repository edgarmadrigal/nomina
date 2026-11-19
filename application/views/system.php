<style>
/* Gráficas de Inicio (c3) */
#graficaDias, #graficaPlantas { cursor: pointer; }
.kpi { border-radius: 6px; padding: 12px 16px; color: #fff; cursor: pointer; }
.kpi h2 { margin: 0; color: #fff !important; font-weight: 600; }
/* etiqueta (Empleados, Asistencias...): blanca y legible también en modo nocturno, que pinta <small> de gris */
.kpi small, html.modo-nocturno .kpi small {
    display: block; color: #fff !important; opacity: 1; font-size: 14px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .4px; text-shadow: 0 1px 2px rgba(0, 0, 0, .25);
}
/* textos de las gráficas más grandes */
#graficaDias .c3-axis text, #graficaPlantas .c3-axis text { font-size: 12px; }
#graficaDias .c3-legend-item text, #graficaPlantas .c3-legend-item text { font-size: 13px; }
#graficaPlantas .c3-chart-arc text { font-size: 14px; font-weight: bold; fill: #fff !important; text-shadow: 0 1px 2px rgba(0, 0, 0, .5); }
.kpi-asistencias { background: #20aee3; }
.kpi-faltas      { background: #ef5350; }
.kpi-retardos    { background: #f4b400; }
.kpi-empleados   { background: #6772e5; cursor: default; }
.kpi-vacaciones  { background: #26a69a; }
.kpi-incidencias { background: #8e5ad8; }
html.modo-nocturno .c3 text { fill: #e3e6ea !important; }
html.modo-nocturno .c3 path.domain, html.modo-nocturno .c3 .tick line { stroke: #6b737e !important; }
html.modo-nocturno .c3-tooltip td { background: #262a31 !important; color: #e3e6ea !important; }
html.modo-nocturno .c3-tooltip th { background: #2f343c !important; color: #e3e6ea !important; }
</style>

        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <div class="container-fluid">
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h3 class="text-themecolor">Asistencia</h3>
                        <small class="text-muted">Datos de las semanas ya procesadas (botón Procesar de Checadas). Vacaciones e incapacidades/permisos van aparte, no como asistencia; los días que aún no llegan no cuentan.</small>
                    </div>
                    <div class="col-md-7 align-self-center">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <select class="custom-select" id="dashSemana"></select>
                            </div>
                            <div class="col-md-5">
                                <select class="custom-select" id="dashPlanta"><option value="">Todas mis plantas</option></select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Indicadores de la semana (clic = detalle de toda la semana) -->
                <div class="row" id="dashKpis">
                    <div class="col-6 col-md-4 col-xl-2 mb-3"><div class="kpi kpi-empleados"><small>Empleados</small><h2 id="kpiEmpleados">—</h2></div></div>
                    <div class="col-6 col-md-4 col-xl-2 mb-3"><div class="kpi kpi-asistencias" data-tipo="asistencias" title="Ver detalle"><small>Asistencias</small><h2 id="kpiAsistencias">—</h2></div></div>
                    <div class="col-6 col-md-4 col-xl-2 mb-3"><div class="kpi kpi-faltas" data-tipo="faltas" title="Ver detalle"><small>Faltas</small><h2 id="kpiFaltas">—</h2></div></div>
                    <div class="col-6 col-md-4 col-xl-2 mb-3"><div class="kpi kpi-retardos" data-tipo="retardos" title="Ver detalle"><small>Retardos</small><h2 id="kpiRetardos">—</h2></div></div>
                    <div class="col-6 col-md-4 col-xl-2 mb-3"><div class="kpi kpi-vacaciones" data-tipo="vacaciones" title="Ver detalle"><small>Vacaciones</small><h2 id="kpiVacaciones">—</h2></div></div>
                    <div class="col-6 col-md-4 col-xl-2 mb-3"><div class="kpi kpi-incidencias" data-tipo="incidencias" title="Ver detalle (incapacidades, permisos...)"><small>Incap. / Permisos</small><h2 id="kpiIncidencias">—</h2></div></div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Asistencia por día <small class="text-muted">— clic en una barra para ver los empleados</small></h5>
                                <div id="graficaDias" style="height: 340px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Faltas por planta <small class="text-muted">— clic para detalle</small></h5>
                                <div id="graficaPlantas" style="height: 340px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Container fluid -->

<!-- Modal detalle -->
<div class="modal fade" id="modalDetalleDash" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:95vw;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleDashTitulo">Detalle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row align-items-center mb-2">
                    <div class="col-7 d-flex align-items-center">
                        <input type="text" class="form-control" id="detalleDashBuscar" placeholder="Buscar número, nombre, departamento...">
                        <label id="detalleDashSolo3Wrap" class="ml-2 mb-0 text-nowrap" style="display:none;"><input type="checkbox" id="detalleDashSolo3"> Solo +3 retardos</label>
                    </div>
                    <div class="col-5 text-right"><strong id="detalleDashResumen"></strong>
                        <button class="btn btn-sm btn-info ml-2" id="detalleDashExcel"><i class="fa fa-file-excel-o"></i> Exportar</button></div>
                </div>
                <div style="max-height:62vh; overflow:auto;">
                    <table class="table table-sm table-striped table-bordered" id="tablaDetalleDash" style="white-space:nowrap;">
                        <thead id="detalleDashCabeza"></thead>
                        <tbody id="detalleDashCuerpo"></tbody>
                    </table>
                </div>
                <style>
                #tablaDetalleDash td, #tablaDetalleDash th { vertical-align: middle; font-size: 12.5px; }
                #tablaDetalleDash thead th { position: sticky; top: 0; background: #f4f6f8; z-index: 2; }
                #tablaDetalleDash .dd-dia { text-align: center; border-left: 2px solid #cfd6de; }
                #tablaDetalleDash td.dd-dia-ini { border-left: 2px solid #cfd6de; }
                #tablaDetalleDash .dd-fcol { width: 100%; font-size: 11px; padding: 1px 4px; }
                #tablaDetalleDash tr.dd-mas3 > td { background: rgba(229,57,53,.12); }
                html.modo-nocturno #tablaDetalleDash thead th { background: #2b3038; }
                html.modo-nocturno #tablaDetalleDash tr.dd-mas3 > td { background: rgba(229,57,53,.25); }
                </style>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/dashboard.js?v=<?php echo filemtime(FCPATH.'assets/js/dashboard.js'); ?>"></script>
