<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Catálogo de Días Festivos</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-2">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;">Año</label>
                                    <select class="custom-select col-12" id="filtroAnio"></select>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;">&nbsp;</label><br>
                                    <button id="btnGenerar" class="btn btn-info">
                                        <i class="fa fa-magic"></i> Generar festivos de ese año
                                    </button>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;">&nbsp;</label><br>
                                    <button id="btnNuevo" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Agregar festivo
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12" style="text-align: -webkit-center;">
                                <center>
                                    <div class="loadTable" style="display:none;"></div>
                                </center>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <table class="table table-striped table-bordered" id="tablaFestivos">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Día</th>
                                            <th>Descripción</th>
                                            <th>Año</th>
                                            <th style="width:120px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoTablaFestivos"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar/Editar -->
<div class="modal fade" id="modalFestivo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFestivoTitulo">Agregar día festivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="festivoId" value="">
                <div class="form-group">
                    <label style="font-weight: bold!important;">Fecha</label>
                    <input type="date" class="form-control" id="festivoFecha">
                </div>
                <div class="form-group">
                    <label style="font-weight: bold!important;">Descripción</label>
                    <input type="text" class="form-control" id="festivoDescripcion" maxlength="100"
                           placeholder="Ej. Día de la Independencia">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarFestivo">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/diasFestivos.js"></script>
