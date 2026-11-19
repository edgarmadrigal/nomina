<!-- Reportes > Nómina consolidada Microsip (controllers/nominaConsolidada.php) -->
<div class="page-wrapper">
    <div class="container-fluid" id="ncon">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title nc-noprint"><i class="fa fa-building-o"></i> Nómina consolidada Microsip</h4>
                <p class="text-muted nc-ayuda nc-noprint">Junta las nóminas ya calculadas en Microsip de varias empresas (sustituye a "Reportes Consolidados de Nóminas"). Se lee directo de Microsip; no se modifica nada. Las columnas son los conceptos que se pagaron: primero percepciones y luego deducciones.</p>

                <div class="card nc-noprint">
                    <div class="card-body">
                        <div class="nc-fila mb-2">
                            <label class="mb-0">Nóminas con fecha del:</label>
                            <input type="date" class="form-control form-control-sm" id="ncFi" style="max-width:160px;">
                            <label class="mb-0">al:</label>
                            <input type="date" class="form-control form-control-sm" id="ncFf" style="max-width:160px;">
                            <label class="mb-0 nc-solo-nomina">Tipo:</label>
                            <select class="form-control form-control-sm nc-solo-nomina" id="ncTipo" style="max-width:190px;">
                                <option value="N">Ordinaria</option>
                                <option value="E">Extraordinaria</option>
                                <option value="A">Aguinaldo</option>
                                <option value="L">Liquidación</option>
                                <option value="R">Reparto de utilidades</option>
                                <option value="T">Todos los tipos</option>
                            </select>
                            <label class="mb-0 nc-check nc-solo-nomina"><input type="checkbox" id="ncAplicadas"> Solo nóminas aplicadas</label>
                        </div>
                        <div class="nc-fila mb-2 nc-solo-imss" style="display:none;">
                            <b>Movimientos:</b>
                            <label class="mb-0 nc-check"><input type="checkbox" class="ncMov" value="I" checked> Altas</label>
                            <label class="mb-0 nc-check"><input type="checkbox" class="ncMov" value="R" checked> Reingresos</label>
                            <label class="mb-0 nc-check"><input type="checkbox" class="ncMov" value="B" checked> Bajas</label>
                            <label class="mb-0 nc-check"><input type="checkbox" class="ncMov" value="S" checked> Modificación de salario</label>
                            <label class="mb-0 ml-3">Agrupar por:</label>
                            <select class="form-control form-control-sm" id="ncAgrupar" style="max-width:200px;">
                                <option value="regPatronal">Registro patronal</option>
                                <option value="grupo">Grupo de pago</option>
                                <option value="empresa">Empresa</option>
                            </select>
                        </div>
                        <div class="mb-1">
                            <b>Empresas</b>
                            <a href="#" id="ncTodas" class="ml-2">Todas</a> · <a href="#" id="ncNinguna">Ninguna</a>
                        </div>
                        <div id="ncEmpresas" class="nc-empresas"><span class="text-muted">Cargando empresas…</span></div>
                        <div class="nc-fila mt-3">
                            <div class="btn-group btn-group-sm" role="group" id="ncVista">
                                <button type="button" class="btn btn-outline-primary active" data-vista="concentrada"><i class="fa fa-users"></i> Concentrada por empleado</button>
                                <button type="button" class="btn btn-outline-primary" data-vista="costo"><i class="fa fa-calculator"></i> Costo total por empresa y depto.</button>
                                <button type="button" class="btn btn-outline-primary" data-vista="dia"><i class="fa fa-sitemap"></i> Directos, Indirectos y Admin.</button>
                                <button type="button" class="btn btn-outline-primary" data-vista="ret"><i class="fa fa-university"></i> Retenciones</button>
                                <button type="button" class="btn btn-outline-primary" data-vista="pen"><i class="fa fa-child"></i> Pensiones alimenticias</button>
                                <button type="button" class="btn btn-outline-primary" data-vista="imss"><i class="fa fa-id-card-o"></i> Movimientos IMSS</button>
                            </div>
                            <button type="button" class="btn btn-info btn-sm" id="ncGenerar"><i class="fa fa-search"></i> Generar</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="ncImprimir" disabled><i class="fa fa-print"></i> Imprimir</button>
                            <button type="button" class="btn btn-success btn-sm" id="ncExcel" disabled><i class="fa fa-file-excel-o"></i> Excel</button>
                            <input type="text" class="form-control form-control-sm" id="ncBuscar" placeholder="Buscar empleado, depto…" style="max-width:220px;" disabled>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" id="ncHoja">
                        <div class="text-center text-muted">Elige las fechas de la nómina y las empresas, y da <b>Generar</b>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#ncon .nc-fila { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
#ncon .nc-ayuda { font-size: 13px; }
#ncon .nc-check { font-weight: normal; cursor: pointer; }
#ncon .nc-check input { position: static; opacity: 1; left: auto; margin-right: 4px; }
#ncon .nc-empresas { display: flex; flex-wrap: wrap; gap: 4px 14px; max-height: 150px; overflow-y: auto; font-size: 13px; }
#ncon .nc-empresas label { font-weight: normal; margin: 0; cursor: pointer; min-width: 200px; }
#ncon .nc-empresas input { position: static; opacity: 1; left: auto; margin-right: 4px; }
/* la hoja va en blanco (también en tema oscuro), como un reporte impreso */
#ncHoja { background: #fff; color: #222; overflow: auto; max-height: 75vh; }
#ncHoja .nc-enc { display: flex; justify-content: space-between; margin-bottom: 8px; }
#ncHoja .nc-enc h3 { margin: 0; font-weight: bold; color: #222; }
#ncHoja .nc-enc .nc-sub { font-size: 13px; color: #222; }
#ncHoja .nc-enc .nc-imp { font-size: 12px; text-align: right; color: #444; }
#ncHoja .nc-aviso { background: #fff3cd; color: #6b5100; padding: 6px 10px; border-radius: 4px; margin-bottom: 8px; font-size: 13px; }
#ncHoja table { border-collapse: collapse; font-size: 11px; color: #222; background: #fff; }
#ncHoja th { position: sticky; top: 0; z-index: 2; background: #f2f2f2; border-bottom: 2px solid #333; padding: 4px 5px; text-align: center; vertical-align: bottom; color: #222; min-width: 64px; }
#ncHoja th.nc-txt { text-align: left; }
#ncHoja th .nc-clv { display: block; font-size: 10px; color: #555; font-weight: normal; }
#ncHoja th.nc-ded { background: #fbeaea; }
#ncHoja th.nc-tot { background: #e6eefb; }
#ncHoja td { padding: 2px 5px; color: #222; background: #fff; border-bottom: 1px solid #eee; white-space: nowrap; }
#ncHoja .num { text-align: right; }
#ncHoja tr.nc-g1 td { background: #dde6f5; font-weight: bold; font-size: 12px; padding-top: 6px; }
#ncHoja tr.nc-g2 td { background: #f3f6fb; font-weight: bold; }
#ncHoja tr.nc-sub td { border-top: 1px solid #333; font-weight: bold; background: #fafafa; }
#ncHoja tr.nc-subemp td { border-top: 2px solid #333; font-weight: bold; background: #eef2f8; }
#ncHoja tr.nc-totg td { border-top: 3px double #333; font-weight: bold; font-size: 12px; background: #e6eefb; }
#ncHoja tr.nc-noapl td { color: #8a6d00; }
#ncHoja select.nc-clasif { font-size: 11px; padding: 0 2px; height: 22px; color: #222; background: #fff; border: 1px solid #bbb; }
#ncHoja .nc-clasif-txt { display: none; }
#ncHoja .nc-pend { color: #b00020; font-weight: bold; }
#ncHoja .nc-ayuda-hoja { font-size: 12px; color: #555; margin-bottom: 6px; }
#ncHoja .nc-cheque { font-size: 11px; padding: 0 6px; line-height: 18px; }
#ncHoja tr.nc-mov-B td { color: #b00020; }
#ncHoja tr.nc-mov-I td, #ncHoja tr.nc-mov-R td { color: #1b6e20; }
@media print {
    body * { visibility: hidden; }
    #ncHoja, #ncHoja * { visibility: visible; }
    #ncHoja { position: absolute; left: 0; top: 0; width: 100%; max-height: none; overflow: visible; }
    #ncHoja th { position: static; }
    #ncHoja select.nc-clasif, #ncHoja .nc-ayuda-hoja, #ncHoja .nc-cheque { display: none; }
    #ncHoja .nc-clasif-txt { display: inline; }
    @page { size: landscape; margin: 8mm; }
}
</style>

<script src="assets/js/nominaConsolidada.js?v=<?php echo filemtime(FCPATH.'assets/js/nominaConsolidada.js'); ?>"></script>
