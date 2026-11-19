<div class="page-wrapper">
    <div class="container-fluid" id="prenom">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title"><i class="fa fa-calculator"></i> Prenómina</h4>
                <p class="text-muted pn-ayuda">Calcula por planta y semana el sueldo, premios, pago de tiempo extra y descuentos por falta/retardo de cada empleado <b>activo</b>, con lo que el sistema ya procesó (checadas de los relojes) y el salario de Microsip. <b>Primero procesa la semana</b> en Checadas; aquí solo se calcula el pago.</p>

                <div class="card"><div class="card-body">
                    <div class="pn-fila">
                        <label class="mb-0">Planta:</label>
                        <select class="form-control form-control-sm" id="pnPlanta" style="max-width:260px;"></select>
                        <label class="mb-0">Semana:</label>
                        <input type="number" class="form-control form-control-sm" id="pnSemana" min="1" max="53" style="max-width:90px;">
                        <label class="mb-0">Año:</label>
                        <select class="form-control form-control-sm" id="pnAnio" style="max-width:110px;"></select>
                        <button type="button" class="btn btn-info btn-sm" id="pnCalcular"><i class="fa fa-search"></i> Calcular</button>
                        <button type="button" class="btn btn-success btn-sm" id="pnExcel"><i class="fa fa-file-excel-o"></i> Excel</button>
                        <button type="button" class="btn btn-warning btn-sm" id="pnImportarProd" title="Importar el bono de producción desde el Excel de la planta"><i class="fa fa-upload"></i> Importar producción</button>
                        <input type="file" id="pnArchivoProd" accept=".xlsx,.xls" style="display:none;">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="pnConfig" title="Constantes de la planta"><i class="fa fa-cog"></i></button>
                    </div>
                    <div id="pnAviso" class="text-warning mt-2" style="display:none;font-size:13px;"></div>
                </div></div>

                <div id="pnResumen" class="row" style="display:none;"></div>

                <div class="card"><div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-striped" id="pnTabla" style="width:100%">
                            <thead><tr id="pnHead"></tr></thead>
                            <tbody><tr><td class="text-center text-muted">Elige planta, semana y año, y da <b>Calcular</b>.</td></tr></tbody>
                        </table>
                    </div>
                </div></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal constantes -->
<div class="modal fade" id="pnModalConfig" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title"><i class="fa fa-cog"></i> Constantes de la prenómina</h5>
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button></div>
        <div class="modal-body">
            <p class="text-muted" style="font-size:13px;">Cambian por planta. Son los valores que el Excel tiene fijos.</p>
            <div class="form-group"><label>Premio puntualidad/asistencia (se paga x2)</label><input type="number" step="0.01" class="form-control" id="cfgPremio"></div>
            <div class="form-group"><label>Factor de descuento por falta (SD × factor)</label><input type="number" step="0.01" class="form-control" id="cfgFactorFalta"></div>
            <div class="row">
                <div class="col-4 form-group"><label>Tarifa T.E. L-V (por hora)</label><input type="number" step="0.000001" class="form-control" id="cfgTeLV"></div>
                <div class="col-4 form-group"><label>Tarifa T.E. sábado</label><input type="number" step="0.000001" class="form-control" id="cfgTeSab"></div>
                <div class="col-4 form-group"><label>Tarifa T.E. domingo</label><input type="number" step="0.000001" class="form-control" id="cfgTeDom"></div>
            </div>
            <div class="row">
                <div class="col-6 form-group"><label>Días de la semana (sueldo = SD × días)</label><input type="number" class="form-control" id="cfgDias"></div>
                <div class="col-6 form-group"><label>Tope de horas T.E. doble</label><input type="number" step="0.01" class="form-control" id="cfgTope"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-success" id="pnGuardarConfig"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div></div>
</div>

<style>
#prenom .pn-fila { display:flex; align-items:center; flex-wrap:wrap; gap:10px; }
#prenom .pn-ayuda { font-size:13px; }
/* Tabla muy compacta: que entren todas las columnas sin scroll */
#prenom #pnTabla { table-layout:auto; width:100% !important; }
#prenom #pnTabla td, #prenom #pnTabla th { font-size:10.5px; line-height:1.15; padding:1px 4px !important; vertical-align:middle; white-space:nowrap; }
#prenom #pnTabla thead th { border-bottom:2px solid #8a96a3; }
#prenom #pnTabla td.pn-txt { max-width:150px; overflow:hidden; text-overflow:ellipsis; }
#prenom #pnTabla td.pn-edcell { padding:0 2px !important; }
#prenom .pn-edit { width:58px; height:19px; font-size:10.5px; padding:0 3px; text-align:right; border:1px solid #cfd6de; border-radius:3px; background:transparent; color:inherit; }
#prenom .pn-edit:focus { border-color:#1e88e5; outline:none; }
#prenom .pn-edit.pn-ok { border-color:#43a047; background:rgba(67,160,71,.12); }
#prenom td.pn-neg { color:#e53935; }
#prenom td.pn-total { font-weight:700; background:rgba(30,136,229,.08); }
#prenom .pn-kpi .card-body { padding:8px 12px; }
#prenom .pn-kpi small { display:block; color:#8a96a3; }
#prenom .pn-kpi b { font-size:18px; }
#prenom #pnResumen { margin-bottom:8px; }
/* aprovechar todo el ancho */
#prenom.container-fluid { max-width:100%; padding-left:8px; padding-right:8px; }
html.modo-nocturno #prenom .pn-edit { border-color:#3a4150; }
/* Botón de constantes (engrane): visible en ambos modos */
#prenom #pnConfig { border:1px solid #8a96a3; color:#5a6570; }
#prenom #pnConfig:hover { background:#8a96a3; color:#fff; }
html.modo-nocturno #prenom #pnConfig { border-color:#aeb8c2; color:#d3dae1; background:rgba(174,184,194,.12); }
html.modo-nocturno #prenom #pnConfig:hover { background:#aeb8c2; color:#1b1e24; }
/* Botón "Importar producción": texto oscuro legible sobre el naranja en ambos modos */
#prenom #pnImportarProd, html.modo-nocturno #prenom #pnImportarProd { color:#3a2600 !important; font-weight:600; }
#prenom #pnImportarProd i { color:#3a2600 !important; }
</style>

<script src="assets/js/prenomina.js?v=<?php echo filemtime(FCPATH.'assets/js/prenomina.js'); ?>"></script>
