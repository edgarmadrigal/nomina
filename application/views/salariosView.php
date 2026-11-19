<div class="page-wrapper">
    <div class="container-fluid" id="salarios">
        <div class="row">
            <div class="col-12">
                <div class="sal-titulo">
                    <h4 class="card-title mb-0"><i class="fa fa-money"></i> Salarios</h4>
                    <button type="button" class="btn btn-info btn-sm sal-editar-btn" id="salNuevo" style="display:none;"><i class="fa fa-plus"></i> Agregar salario</button>
                </div>
                <p class="text-muted sal-ayuda">Salarios por año con su importe con letra (los usa Reporte Contrato). Un salario no se borra:
                    se da de baja y deja de salir para elegir, así los contratos que ya lo usan no cambian. Los cambios quedan en Historial de cambios.</p>

                <div class="row" id="salResumen"></div>

                <!-- Historial por año -->
                <div class="card">
                    <div class="card-body">
                        <h5>Historial por año</h5>
                        <div id="salGrafica" class="sal-grafica"></div>
                    </div>
                </div>

                <!-- Catálogo -->
                <div class="card">
                    <div class="card-body">
                        <div class="sal-titulo">
                            <h5 class="mb-0">Catálogo</h5>
                            <div class="sal-filtros">
                                <input type="text" class="form-control form-control-sm" id="salBuscar" placeholder="Buscar año, importe o letra">
                                <label class="mb-0"><input type="checkbox" id="salVerBajas"> Ver dados de baja</label>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover sal-tabla">
                                <thead><tr><th>Año</th><th class="text-right">Salario diario</th><th>Importe con letra</th><th>Aumento vs año anterior</th><th>Estado</th><th></th></tr></thead>
                                <tbody id="salTabla"><tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSalario" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="salModalTitulo">Salario</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="salId">
                <div class="row">
                    <div class="col-6 form-group"><label>Año</label><input type="number" class="form-control" id="salAnio" min="1990" max="2100"></div>
                    <div class="col-6 form-group"><label>Salario diario</label><input type="number" class="form-control" id="salImporte" min="0" step="0.01" placeholder="0.00"></div>
                </div>
                <div class="form-group">
                    <label>Importe con letra <small class="text-muted">(se escribe solo al poner el salario; lo puedes corregir)</small></label>
                    <textarea class="form-control" id="salLetra" rows="2" maxlength="150"></textarea>
                </div>
                <div id="salAviso" class="text-muted" style="font-size:12px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="salGuardar"><i class="fa fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<style>
#salarios .sal-titulo { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
#salarios .sal-ayuda { font-size: 13px; }
#salarios .sal-filtros { display: flex; align-items: center; gap: 12px; }
#salarios .sal-filtros input[type=text] { width: 230px; }
#salarios .sal-kpi .card-body { padding: 12px 16px; }
#salarios .sal-kpi small { display: block; color: #8a96a3; }
#salarios .sal-kpi b { font-size: 22px; }
#salarios .sal-grafica { display: flex; align-items: flex-end; gap: 4px; height: 190px; padding-top: 18px; overflow-x: auto; }
#salarios .sal-barra { flex: 1 0 26px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; cursor: pointer; }
#salarios .sal-barra i { display: block; width: 100%; background: linear-gradient(#26c6da, #1e88e5); border-radius: 4px 4px 0 0; min-height: 2px; }
#salarios .sal-barra span { font-size: 10px; color: #8a96a3; margin-top: 3px; }
#salarios .sal-barra em { font-style: normal; font-size: 9.5px; color: #8a96a3; white-space: nowrap; }
#salarios .sal-barra.sel i { background: linear-gradient(#ffb22b, #fb8c00); }
#salarios .sal-tabla td { vertical-align: middle; font-size: 13px; }
#salarios .sal-tabla tr.sal-anio td { background: rgba(30, 136, 229, .08); font-weight: bold; }
#salarios .sal-tabla tr.sal-baja td { opacity: .55; }
#salarios .sal-sube { color: #2e7d32; } #salarios .sal-baja-txt { color: #e53935; }
#salarios .badge { color: #fff !important; }
html.modo-nocturno #modalSalario .modal-content { background: #23272e; color: #dfe3e8; }
html.modo-nocturno #modalSalario .form-control { background: #1f2329; color: #dfe3e8; border-color: #444b55; }
</style>

<script src="assets/js/salarios.js?v=<?php echo filemtime(FCPATH.'assets/js/salarios.js'); ?>"></script>
