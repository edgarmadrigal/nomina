<div class="page-wrapper">
    <div class="container-fluid" id="cmicro">
        <div class="row">
            <div class="col-12">
                <div class="cm-titulo">
                    <h4 class="card-title mb-0"><i class="fa fa-list-alt"></i> Conceptos Microsip</h4>
                </div>
                <div class="cm-sinc">
                    <i class="fa fa-refresh"></i>
                    <span>Se actualiza automáticamente <b>cada hora</b> desde Microsip.
                        Última actualización: <b class="cm-ultima">—</b></span>
                    <button type="button" class="btn btn-info btn-sm cm-actualizar" style="display:none;"><i class="fa fa-cloud-download"></i> Actualizar ahora</button>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="cm-fila">
                            <label class="mb-0">Empresa:</label>
                            <select class="form-control form-control-sm" id="cmEmpresa"><option value="">Cargando…</option></select>
                        </div>
                    </div>
                </div>

                <!-- Claves del TXT -->
                <div class="card cm-requiere-empresa" style="display:none;">
                    <div class="card-body">
                        <h5>Claves que usa el TXT de asistencia</h5>
                        <p class="text-muted cm-ayuda">Cada renglón <code>|1.1|clave,valor</code> del TXT sale con la clave de Microsip que elijas aquí,
                            así el archivo se importa tal cual. Si una clave no existe en el Microsip de esta empresa se marca en rojo.</p>
                        <div class="table-responsive">
                            <table class="table table-sm cm-tabla">
                                <thead><tr><th>Renglón del TXT</th><th>Clave en Microsip</th><th>Estado</th></tr></thead>
                                <tbody id="cmTxt"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Catálogo -->
                <div class="card cm-requiere-empresa" style="display:none;">
                    <div class="card-body">
                        <div class="cm-titulo">
                            <h5 class="mb-0">Catálogo <small class="text-muted cm-cuenta"></small></h5>
                            <div class="cm-fila">
                                <input type="text" class="form-control form-control-sm" id="cmBuscar" placeholder="Buscar clave o nombre">
                                <select class="form-control form-control-sm" id="cmNaturaleza">
                                    <option value="">Percepciones y deducciones</option>
                                    <option value="P">Percepciones</option>
                                    <option value="R">Deducciones (retenciones)</option>
                                </select>
                                <label class="mb-0 cm-check"><input type="checkbox" id="cmVerBajas"> Ver los que ya no existen</label>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover cm-tabla">
                                <thead><tr><th>Clave</th><th>Nombre</th><th>Naturaleza</th><th>Uso en el TXT</th><th>Última modificación</th></tr></thead>
                                <tbody id="cmTabla"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#cmicro .cm-titulo { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
#cmicro .cm-sinc { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; padding: 8px 12px; margin-bottom: 12px; border-radius: 4px;
                   background: rgba(30, 136, 229, .1); border: 1px solid rgba(30, 136, 229, .35); font-size: 13px; }
#cmicro .cm-sinc .fa-refresh { color: #1e88e5; }
#cmicro .cm-sinc .cm-error { color: #e53935; }
#cmicro .cm-sinc .btn { margin-left: auto; }
#cmicro .cm-fila { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
#cmicro #cmEmpresa { max-width: 340px; }
#cmicro #cmBuscar { width: 220px; }
#cmicro #cmNaturaleza { width: auto; }
#cmicro .cm-check { display: inline-flex !important; align-items: center; float: none !important; width: auto !important; }
#cmicro .cm-check input { position: static !important; left: auto !important; opacity: 1 !important; display: inline-block !important; width: auto !important; height: auto !important; margin: 0 4px 0 0; }
#cmicro .cm-ayuda { font-size: 13px; }
#cmicro .cm-tabla td { vertical-align: middle; font-size: 13px; }
#cmicro .cm-tabla select { max-width: 420px; }
#cmicro .cm-tabla tr.cm-baja td { opacity: .5; text-decoration: line-through; }
#cmicro .badge { color: #fff !important; }
#cmicro .cm-ok { color: #2e7d32; } #cmicro .cm-mal { color: #e53935; font-weight: bold; }
html.modo-nocturno #cmicro .cm-ok { color: #66bb6a; }
</style>

<script src="assets/js/conceptosMicrosip.js?v=<?php echo filemtime(FCPATH.'assets/js/conceptosMicrosip.js'); ?>"></script>
