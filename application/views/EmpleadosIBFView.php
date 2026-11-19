<div class="page-wrapper">
    <div class="container-fluid" id="eibf">
        <div class="row">
            <div class="col-12">
                <div class="ei-titulo">
                    <h4 class="card-title mb-0"><i class="fa fa-address-card"></i> Empleados IBF</h4>
                    <button type="button" class="btn btn-info" id="eiNuevo"><i class="fa fa-plus-circle"></i> Agregar empleado</button>
                </div>
                <p class="text-muted ei-ayuda">IBF no está en Microsip: sus empleados se capturan aquí. El número es el que usa para checar en el reloj. Las bajas no se borran (se conserva su historial) y se pueden reactivar.</p>

                <div class="row" id="eiKpis">
                    <div class="col-md-3 col-6"><div class="card ei-kpi ei-filtro activo" data-filtro="A"><div class="card-body"><small>Activos</small><b class="text-success" id="kActivos">0</b></div></div></div>
                    <div class="col-md-3 col-6"><div class="card ei-kpi ei-filtro" data-filtro="B"><div class="card-body"><small>Bajas</small><b class="text-danger" id="kBajas">0</b></div></div></div>
                    <div class="col-md-3 col-6"><div class="card ei-kpi ei-filtro" data-filtro=""><div class="card-body"><small>Todos</small><b class="text-info" id="kTodos">0</b></div></div></div>
                    <div class="col-md-3 col-6"><div class="card ei-kpi"><div class="card-body"><small>Departamentos</small><b class="text-secondary" id="kDeptos">0</b></div></div></div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped" id="eiTabla" style="width:100%">
                                <thead><tr><th>Número</th><th>Nombre completo</th><th>Puesto</th><th>Departamento</th><th>RFC</th><th>IMSS</th><th>Ingreso</th><th>Estatus</th><th class="text-center">Acciones</th></tr></thead>
                                <tbody><tr><td colspan="9" class="text-center text-muted">Cargando…</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eiModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="eiModalTitulo">Agregar empleado</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eiId">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Número de empleado *</label><input type="text" class="form-control" id="eiNumero" inputmode="numeric" maxlength="10" placeholder="Ej. 82792"></div>
                    <div class="col-md-4 form-group"><label>Fecha de ingreso</label><input type="date" class="form-control" id="eiFecha"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group"><label>Apellido paterno *</label><input type="text" class="form-control ei-may" id="eiPaterno"></div>
                    <div class="col-md-4 form-group"><label>Apellido materno</label><input type="text" class="form-control ei-may" id="eiMaterno"></div>
                    <div class="col-md-4 form-group"><label>Nombre(s) *</label><input type="text" class="form-control ei-may" id="eiNombres"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group"><label>Puesto</label><input type="text" class="form-control ei-may" id="eiPuesto" list="eiListaPuestos"><datalist id="eiListaPuestos"></datalist></div>
                    <div class="col-md-6 form-group"><label>Departamento</label><input type="text" class="form-control ei-may" id="eiDepto" list="eiListaDeptos"><datalist id="eiListaDeptos"></datalist></div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group"><label>RFC</label><input type="text" class="form-control ei-may" id="eiRfc" maxlength="13"></div>
                    <div class="col-md-6 form-group"><label>Núm. IMSS</label><input type="text" class="form-control" id="eiImss" inputmode="numeric" maxlength="11"></div>
                </div>
                <div class="ei-vista">Así queda el nombre: <b id="eiVista">—</b></div>
                <div class="text-danger mt-2" id="eiError"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="eiGuardar"><i class="fa fa-save"></i> Guardar</button>
            </div>
        </div>
    </div>
</div>

<style>
#eibf .ei-titulo { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 6px; }
#eibf .ei-ayuda { font-size: 13px; }
#eibf .ei-kpi .card-body { padding: 10px 14px; }
#eibf .ei-kpi small { display: block; color: #8a96a3; }
#eibf .ei-kpi b { font-size: 22px; }
#eibf .ei-filtro { cursor: pointer; border: 2px solid transparent; }
#eibf .ei-filtro.activo { border-color: #20aee3; }
#eibf #eiTabla td { vertical-align: middle; font-size: 13px; }
#eibf .badge { color: #fff !important; }
#eiModal label { font-weight: bold; }
#eiModal .ei-may { text-transform: uppercase; }
#eiModal .ei-vista { font-size: 13px; padding: 6px 10px; border-radius: 4px; background: rgba(32, 174, 227, .12); }
</style>

<script src="assets/js/empleadosIBF.js?v=<?php echo filemtime(FCPATH.'assets/js/empleadosIBF.js'); ?>"></script>
