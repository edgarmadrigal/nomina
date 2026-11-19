<div class="page-wrapper">
    <div class="container-fluid" id="racum">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title"><i class="fa fa-calculator"></i> Acumulado de Conceptos Microsip</h4>
                <p class="text-muted ra-ayuda">Suma de importes y unidades de la nómina de Microsip (pagos aplicados) por concepto, en el año que elijas. Elige una empresa o <b>Todas</b>. El botón verde baja un Excel con una <b>hoja por empresa</b> (detalle por empleado del año); puede tardar ~1 min.</p>

                <div class="card">
                    <div class="card-body">
                        <div class="ra-fila">
                            <label class="mb-0">Empresa:</label>
                            <select class="form-control form-control-sm" id="acEmpresa" style="max-width:300px;"><option value="">Todas las empresas</option></select>
                            <label class="mb-0">Año:</label>
                            <select class="form-control form-control-sm" id="acAnio" style="max-width:120px;"></select>
                            <button type="button" class="btn btn-info btn-sm" id="acGenerar"><i class="fa fa-search"></i> Generar</button>
                            <button type="button" class="btn btn-success btn-sm" id="acExcelDetalle" title="Descarga un Excel con una hoja por empresa (detalle por empleado, conceptos en columnas) del año elegido"><i class="fa fa-file-excel-o"></i> Excel detalle por empresa</button>
                        </div>
                    </div>
                </div>

                <div id="acResumen" class="row" style="display:none;"></div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped" id="acTabla" style="width:100%">
                                <thead><tr><th>Clave</th><th>Concepto</th><th>Tipo</th><th class="text-right">Unidades</th><th class="text-right">Movimientos</th><th class="text-right">Importe</th><th class="text-right ac-col-emp">Empresas</th></tr></thead>
                                <tbody><tr><td colspan="7" class="text-center text-muted">Elige empresa y fechas, y da <b>Generar</b>.</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#racum .ra-fila { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
#racum .ra-ayuda { font-size: 13px; }
#racum .ac-kpi .card-body { padding: 10px 14px; }
#racum .ac-kpi small { display: block; color: #8a96a3; }
#racum .ac-kpi b { font-size: 20px; }
#racum #acResumen { margin-bottom: 10px; }
#racum #acTabla td { vertical-align: middle; font-size: 13px; }
#racum .badge { color: #fff !important; }
</style>

<script src="assets/js/reporteConceptosMicrosip.js?v=<?php echo filemtime(FCPATH.'assets/js/reporteConceptosMicrosip.js'); ?>"></script>
