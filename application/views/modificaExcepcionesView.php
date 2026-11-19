<!-- Importar/Exportar > Modifica excepciones Microsip (controllers/modificaExcepciones.php) -->
<div class="page-wrapper">
    <div class="container-fluid" id="mexc">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title"><i class="fa fa-pencil-square-o"></i> Modifica excepciones Microsip</h4>
                <p class="text-muted mx-ayuda">Pone en las excepciones de una nómina de Microsip las cuotas que vienen en un Excel (sustituye a "Modifica Excepciones" de SOTI).
                    El Excel lleva en la <b>columna A el número de empleado</b> y en la <b>fila 1 el nombre o la clave del concepto</b> de Microsip (puede traer una columna "NOMBRE", se ignora).
                    Solo se tocan los empleados que <b>ya tienen excepción</b> en esa nómina; las celdas vacías o en 0 no se tocan. Primero sale una vista previa y nada se guarda hasta que das <b>Aplicar en Microsip</b>.</p>

                <div class="card">
                    <div class="card-body">
                        <div class="mx-fila">
                            <label class="mb-0">Empresa:</label>
                            <select class="form-control form-control-sm" id="mxEmpresa" style="max-width:260px;"><option value="">Cargando…</option></select>
                            <label class="mb-0">Nómina:</label>
                            <select class="form-control form-control-sm" id="mxNomina" style="max-width:360px;" disabled><option value="">Elige la empresa</option></select>
                        </div>
                        <div class="mx-fila mt-2">
                            <label class="mb-0">Archivo:</label>
                            <input type="file" class="form-control-file" id="mxArchivo" accept=".xlsx,.xls" style="max-width:360px;">
                            <button type="button" class="btn btn-info btn-sm" id="mxPrevia"><i class="fa fa-eye"></i> Vista previa</button>
                            <button type="button" class="btn btn-danger btn-sm" id="mxAplicar" disabled><i class="fa fa-check"></i> Aplicar en Microsip</button>
                            <button type="button" class="btn btn-success btn-sm" id="mxExcel" disabled><i class="fa fa-file-excel-o"></i> Excel</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" id="mxHoja">
                        <div class="text-center text-muted">Elige la empresa, la nómina y el Excel, y da <b>Vista previa</b>.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#mexc .mx-fila { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
#mexc .mx-ayuda { font-size: 13px; }
#mxHoja { background: #fff; color: #222; overflow: auto; max-height: 72vh; }
#mxHoja .mx-res { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 13px; margin-bottom: 8px; }
#mxHoja .mx-res b { font-size: 15px; }
#mxHoja .mx-aviso { background: #fff3cd; color: #6b5100; padding: 6px 10px; border-radius: 4px; margin-bottom: 8px; font-size: 12px; max-height: 120px; overflow: auto; }
#mxHoja table { border-collapse: collapse; font-size: 11px; color: #222; background: #fff; }
#mxHoja th { position: sticky; top: 0; z-index: 2; background: #f2f2f2; border-bottom: 2px solid #333; padding: 4px 6px; text-align: center; vertical-align: bottom; color: #222; min-width: 70px; }
#mxHoja th .mx-clv { display: block; font-size: 10px; color: #555; font-weight: normal; }
#mxHoja td { padding: 2px 6px; color: #222; background: #fff; border-bottom: 1px solid #eee; white-space: nowrap; }
#mxHoja td.num { text-align: right; }
#mxHoja td.mx-U { background: #c8ffc8; }
#mxHoja td.mx-I { background: #c8e1ff; }
#mxHoja td.mx-eq { background: #e6e6e6; color: #666; }
#mxHoja td.mx-off { background: #fff; color: #999; text-decoration: line-through; }
#mxHoja td .mx-ant { display: block; font-size: 10px; color: #555; }
#mxHoja td input { position: static; opacity: 1; left: auto; margin: 0 3px 0 0; vertical-align: middle; }
#mxHoja tr.mx-est-I td, #mxHoja tr.mx-est-X td { color: #999; }
#mxHoja tr.mx-est-X td.mx-nom { color: #b00020; }
#mxHoja tr.mx-ok td.mx-U, #mxHoja tr.mx-ok td.mx-I { font-weight: bold; }
#mxHoja .mx-leyenda span { display: inline-block; padding: 0 6px; margin-right: 6px; border: 1px solid #ccc; }
</style>

<script src="assets/js/modificaExcepciones.js?v=<?php echo filemtime(FCPATH.'assets/js/modificaExcepciones.js'); ?>"></script>
