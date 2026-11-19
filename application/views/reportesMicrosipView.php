<div class="page-wrapper">
    <div class="container-fluid" id="rmic">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title rm-noprint"><i class="fa fa-file-text-o"></i> Reportes Microsip</h4>
                <p class="text-muted rm-ayuda rm-noprint">Los mismos reportes del Reporteador de Microsip Nómina (Catálogo de empleados, Fonacot e Infonavit x empleado), leídos directo de Microsip. Elige reporte, empresa y el rango de fechas de pago.</p>

                <div class="card rm-noprint">
                    <div class="card-body">
                        <div class="rm-fila">
                            <label class="mb-0">Reporte:</label>
                            <select class="form-control form-control-sm" id="rmTipo" style="max-width:230px;">
                                <option value="catalogo">Catálogo de empleados</option>
                                <option value="fonacot">Fonacot</option>
                                <option value="infonavit">Infonavit x empleado</option>
                            </select>
                            <label class="mb-0">Empresa:</label>
                            <select class="form-control form-control-sm" id="rmEmpresa" style="max-width:260px;"><option value="">Elige…</option></select>
                            <label class="mb-0">Del:</label>
                            <input type="date" class="form-control form-control-sm" id="rmFi" style="max-width:160px;">
                            <label class="mb-0">al:</label>
                            <input type="date" class="form-control form-control-sm" id="rmFf" style="max-width:160px;">
                            <button type="button" class="btn btn-info btn-sm" id="rmGenerar"><i class="fa fa-search"></i> Generar</button>
                            <button type="button" class="btn btn-secondary btn-sm" id="rmImprimir" disabled><i class="fa fa-print"></i> Imprimir</button>
                            <button type="button" class="btn btn-success btn-sm" id="rmExcel" disabled><i class="fa fa-file-excel-o"></i> Excel</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" id="rmHoja">
                        <div class="text-center text-muted">Elige reporte, empresa y fechas, y da <b>Generar</b>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#rmic .rm-fila { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
#rmic .rm-ayuda { font-size: 13px; }
/* la hoja del reporte va en blanco como la de Microsip, también en tema oscuro */
#rmHoja { background: #fff; color: #222; overflow-x: auto; }
#rmHoja .rm-enc { display: flex; justify-content: space-between; margin-bottom: 10px; }
#rmHoja .rm-enc h3 { margin: 0; font-weight: bold; color: #222; }
#rmHoja .rm-enc .rm-emp { font-size: 14px; color: #222; }
#rmHoja .rm-enc .rm-imp { font-size: 12px; text-align: right; color: #444; }
#rmHoja table { width: 100%; border-collapse: collapse; font-size: 12px; color: #222; background: #fff; }
#rmHoja th { border-bottom: 2px solid #333; padding: 4px 6px; text-align: left; white-space: nowrap; color: #222; background: #fff; }
#rmHoja td { padding: 2px 6px; vertical-align: top; color: #222; background: #fff; }
#rmHoja .num { text-align: right; white-space: nowrap; }
#rmHoja .nw { white-space: nowrap; }
#rmHoja tr.rm-emp td { padding-top: 10px; font-weight: bold; }
#rmHoja tr.rm-sub td { border-top: 1px solid #333; font-weight: bold; padding-bottom: 6px; }
#rmHoja tr.rm-tot td { border-top: 2px solid #333; font-weight: bold; font-size: 13px; padding-top: 6px; }
#rmHoja tr.rm-baja td { color: #b00020; }
@media print {
    body * { visibility: hidden; }
    #rmHoja, #rmHoja * { visibility: visible; }
    #rmHoja { position: absolute; left: 0; top: 0; width: 100%; overflow: visible; }
    @page { size: landscape; margin: 10mm; }
}
</style>

<script src="assets/js/reportesMicrosip.js?v=<?php echo filemtime(FCPATH.'assets/js/reportesMicrosip.js'); ?>"></script>
