<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Catálogo de Relojes Checadores</h4>
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-end">
                            <div class="col-6">
                                <div class="form-group">
                                    <button id="btnNuevo" class="btn btn-success">
                                        <i class="fa fa-plus"></i> Agregar reloj
                                    </button>
                                    <button id="btnActualizar" class="btn btn-info">
                                        <i class="fa fa-refresh"></i> Actualizar
                                    </button>
                                    <button id="btnDescargarTodos" class="btn btn-primary">
                                        <i class="fa fa-download"></i> Descargar todos
                                    </button>
                                    <button id="btnComunicacion" class="btn btn-warning" title="Poner a tiempo, ver la hora o borrar checadas de varios relojes">
                                        <i class="fa fa-exchange"></i> Comunicación con relojes
                                    </button>
                                    <button id="btnUsuariosReloj" class="btn btn-secondary" title="Dar de alta empleados en los relojes, copiar rostros y quitar bajas">
                                        <i class="fa fa-users"></i> Usuarios en relojes
                                    </button>
                                    <span id="avanceDescarga" style="margin-left:10px;"></span>
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
                            <div class="col-12 table-responsive">
                                <table class="table table-striped table-bordered" id="tablaRelojes">
                                    <thead>
                                        <tr>
                                            <th>Activo</th>
                                            <th>Conexión</th>
                                            <th>Descripción</th>
                                            <th>Tipo</th>
                                            <th>IP : Puerto</th>
                                            <th>ID reloj</th>
                                            <th>Planta respaldo</th>
                                            <th>Desde</th>
                                            <th>Última descarga</th>
                                            <th>Última checada</th>
                                            <th title="Checadas de hoy">Hoy</th>
                                            <th title="Checadas nuevas en la última descarga">Bajadas</th>
                                            <th style="width:110px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cuerpoTablaRelojes"></tbody>
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
<div class="modal fade" id="modalReloj" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalRelojTitulo">Agregar reloj</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="relojId" value="">
                <div class="row">
                    <div class="col-6 form-group">
                        <label style="font-weight: bold!important;">Descripción</label>
                        <input type="text" class="form-control" id="relojDescripcion" maxlength="100" placeholder="Ej. 152 DIRECCION">
                    </div>
                    <div class="col-6 form-group">
                        <label style="font-weight: bold!important;">Tipo de reloj</label>
                        <select class="custom-select col-12" id="relojSdk">
                            <option value="BS2">Suprema BioStar 2 (puerto 51211)</option>
                            <option value="BS1">Suprema FaceStation / BioStar 1 (puerto 1470)</option>
                            <option value="HV">FaceGo / Hanvon (puerto 9922)</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">IP</label>
                        <input type="text" class="form-control" id="relojIp" placeholder="192.168.128.x">
                    </div>
                    <div class="col-2 form-group">
                        <label style="font-weight: bold!important;">Puerto</label>
                        <input type="number" class="form-control" id="relojPuerto" min="1" max="65535">
                    </div>
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">ID del reloj</label>
                        <input type="text" class="form-control" id="relojDeviceId" placeholder="Usa Detectar ID">
                    </div>
                    <div class="col-2 form-group">
                        <label style="font-weight: bold!important;">&nbsp;</label><br>
                        <button type="button" class="btn btn-info" id="btnDetectar" title="Conectarse al reloj y leer su ID">
                            <i class="fa fa-plug"></i> Detectar ID
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">Planta de respaldo</label>
                        <select class="custom-select col-12" id="relojPlanta"></select>
                        <small class="text-muted">Se usa cuando el empleado no se encuentra en el grupo.</small>
                    </div>
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">Pasar checadas desde</label>
                        <input type="date" class="form-control" id="relojFechaInicio">
                    </div>
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">Zona horaria</label>
                        <select class="custom-select col-12" id="relojZona">
                            <option value="Central Standard Time (Mexico)">Centro (Torreón, CDMX)</option>
                            <option value="Mountain Standard Time (Mexico)">Pacífico (Mazatlán)</option>
                            <option value="Pacific Standard Time (Mexico)">Noroeste (Tijuana)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight: bold!important;">Grupo de plantas</label>
                    <small class="text-muted">— la planta de cada checada se busca por número de empleado en estas plantas</small>
                    <div id="relojGrupo" class="row" style="max-height:180px; overflow-y:auto; border:1px solid #ddd; padding:6px; margin:0;"></div>
                </div>
                <label class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="relojActivo">
                    <span class="custom-control-label">Activo (se descarga en cada sincronización)</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarReloj">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Empleados que checaron -->
<div class="modal fade" id="modalEmpleados" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEmpleadosTitulo">Empleados que checaron</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="empleadosRelojId" value="">
                <div class="row align-items-end">
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">Fecha</label>
                        <input type="date" class="form-control" id="empleadosFecha">
                    </div>
                    <div class="col-4 form-group">
                        <label style="font-weight: bold!important;">Buscar</label>
                        <input type="text" class="form-control" id="empleadosBuscar" placeholder="Número o nombre">
                    </div>
                    <div class="col-4 form-group text-right">
                        <strong id="resumenEmpleados"></strong>
                    </div>
                </div>
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Número</th>
                                <th>Nombre</th>
                                <th>Planta</th>
                                <th>Estatus</th>
                                <th>Checadas</th>
                                <th>Primera</th>
                                <th>Última</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoEmpleados"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Checadas de la última descarga -->
<div class="modal fade" id="modalBajadas" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBajadasTitulo">Checadas de la última descarga</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detalleBajadas"></div>
                <div class="row align-items-end">
                    <div class="col-6 form-group">
                        <label style="font-weight: bold!important;">Buscar</label>
                        <input type="text" class="form-control" id="bajadasBuscar" placeholder="Número o nombre">
                    </div>
                    <div class="col-6 form-group text-right">
                        <strong id="resumenBajadas"></strong>
                    </div>
                </div>
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table table-sm table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha y hora</th>
                                <th>Número</th>
                                <th>Nombre</th>
                                <th>Planta</th>
                                <th>Resultado</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoBajadas"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Clave de administrador -->
<div class="modal fade" id="modalClave" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-lock"></i> Clave de administrador</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:13px;">Para modificar la configuración de los relojes se requiere un administrador.</p>
                <div class="form-group">
                    <label style="font-weight: bold!important;">Usuario</label>
                    <input type="text" class="form-control" id="usuarioAdmin" autocomplete="off">
                </div>
                <div class="form-group">
                    <label style="font-weight: bold!important;">Clave</label>
                    <input type="password" class="form-control" id="claveAdmin" autocomplete="new-password">
                </div>
                <div id="mensajeClave" class="text-danger" style="font-size:13px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnAutorizar">Autorizar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Comunicación con relojes (como "Comunicación con Dispositivos" de Nomiplus) -->
<div class="modal fade" id="modalComunicacion" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-exchange"></i> Comunicación con relojes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7">
                        <label class="com-titulo">Marca los relojes con los que quieres comunicarte</label>
                        <div class="com-lista">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr>
                                    <th style="width:34px;"><input type="checkbox" id="comTodos" title="Todos / ninguno"></th>
                                    <th>Reloj</th><th>Tipo</th><th>IP</th>
                                </tr></thead>
                                <tbody id="comRelojes"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="com-titulo">Marca las acciones a realizar</label>
                        <div class="com-lista com-acciones">
                            <label class="com-accion"><input type="checkbox" class="comAccion" value="descargar"> <i class="fa fa-download text-primary"></i> Bajar transacciones (checadas)</label>
                            <label class="com-accion"><input type="checkbox" class="comAccion" value="hora"> <i class="fa fa-clock-o text-info"></i> Ver la hora del reloj</label>
                            <label class="com-accion"><input type="checkbox" class="comAccion" value="ponerhora"> <i class="fa fa-history text-success"></i> Poner a tiempo
                                <small class="d-block text-muted">Pone la hora del servidor. Requiere administrador.</small></label>
                            <label class="com-accion com-peligro"><input type="checkbox" class="comAccion" value="borrar"> <i class="fa fa-trash"></i> Borrar transacciones del reloj
                                <small class="d-block">Primero descarga y guarda todas las checadas; si la descarga falla no borra nada. Pide clave de administrador y confirmación.</small></label>
                            <div class="com-prox text-muted"><i class="fa fa-users"></i> Usuarios y biometría (alta de empleados en el reloj, respaldo y envío de rostros): <b>siguiente fase</b>.</div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 mb-1">
                    <b>Avance</b>
                    <a href="#" id="comVerHistorial" style="font-size:13px;"><i class="fa fa-list"></i> Historial de comunicaciones</a>
                </div>
                <div class="com-avance">
                    <table class="table table-sm mb-0">
                        <thead><tr><th style="width:190px;">Reloj</th><th style="width:170px;">Acción</th><th>Resultado</th></tr></thead>
                        <tbody id="comAvance"><tr><td colspan="3" class="text-muted text-center">Elige relojes y acciones y da <b>Iniciar comunicaciones</b>.</td></tr></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnIniciarCom"><i class="fa fa-play"></i> Iniciar comunicaciones</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmación de borrado de checadas -->
<div class="modal fade" id="modalBorrado" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#c62828; color:#fff;">
                <h5 class="modal-title" style="color:#fff;"><i class="fa fa-exclamation-triangle"></i> Borrar checadas de los relojes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Se van a <b>borrar todas las checadas guardadas en estos relojes</b>:</p>
                <ul id="borradoRelojes" style="font-size:13px;"></ul>
                <p style="font-size:13px;">Antes de borrar, el sistema descarga y guarda todas las checadas de cada reloj. Si la descarga de un reloj falla, ese reloj <b>no se borra</b>. Esta acción no se puede deshacer en el reloj.</p>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label style="font-weight:bold!important;">Usuario administrador</label>
                        <input type="text" class="form-control" id="borradoUsuario" autocomplete="off">
                    </div>
                    <div class="form-group col-6">
                        <label style="font-weight:bold!important;">Contraseña</label>
                        <input type="password" class="form-control" id="borradoClave" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight:bold!important;">Para confirmar escribe <span class="text-danger">BORRAR</span></label>
                    <input type="text" class="form-control" id="borradoConfirma" autocomplete="off" placeholder="BORRAR">
                </div>
                <div id="borradoMensaje" class="text-danger" style="font-size:13px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarBorrado"><i class="fa fa-trash"></i> Sí, borrar checadas</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal usuarios del reloj -->
<div class="modal fade" id="modalUsuarios" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document" style="max-width:1250px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-users"></i> Usuarios en relojes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="usr-fila">
                    <label class="mb-0"><b>Reloj:</b></label>
                    <select class="custom-select" id="usrReloj" style="max-width:300px;"></select>
                    <button type="button" class="btn btn-info" id="btnUsrLeer"><i class="fa fa-eye"></i> Ver usuarios</button>
                    <button type="button" class="btn btn-warning" id="btnUsrActualizar" title="Vuelve a leer la lista del reloj (en segundo plano, puede tardar)"><i class="fa fa-refresh"></i> Actualizar del reloj</button>
                    <input type="text" class="form-control" id="usrBuscar" placeholder="Buscar número o nombre" style="max-width:240px;">
                    <select class="custom-select" id="usrPlanta" style="max-width:240px;" title="Filtrar por planta"><option value="">Todas las plantas</option></select>
                    <span id="usrUltima" class="text-muted" style="font-size:12px;"></span>
                </div>
                <div id="usrFiltros" class="usr-fila mt-2"></div>
                <!-- Progreso de la copia de rostros en segundo plano (se muestra solo si hay una copia en curso o recién terminada) -->
                <div id="usrCopiaProgreso" class="mt-2 p-2" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap" style="font-size:12.5px;">
                        <span><i class="fa fa-clone text-primary"></i> <b id="usrCopiaTexto">Copiando rostros…</b></span>
                        <span id="usrCopiaNums" class="text-muted"></span>
                    </div>
                    <div class="progress mt-1" style="height:18px;">
                        <div id="usrCopiaBarra" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width:0%;">0%</div>
                    </div>
                </div>
                <div class="usr-tabla mt-2">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr>
                            <th style="width:30px;"><input type="checkbox" id="usrTodos" title="Marcar los que se ven"></th>
                            <th class="usr-orden" data-sort="num">Número <i class="fa usr-flecha"></i></th>
                            <th class="usr-orden" data-sort="nombre">Nombre (sistema) <i class="fa usr-flecha"></i></th>
                            <th class="usr-orden" data-sort="nombreReloj">Nombre en el reloj <i class="fa usr-flecha"></i></th>
                            <th class="usr-orden" data-sort="planta">Planta <i class="fa usr-flecha"></i></th>
                            <th class="usr-orden" data-sort="rostros">Rostro <i class="fa usr-flecha"></i></th>
                            <th class="usr-orden" data-sort="estado">Situación <i class="fa usr-flecha"></i></th>
                        </tr></thead>
                        <tbody id="usrCuerpo"><tr><td colspan="7" class="text-center text-muted">Elige un reloj y da <b>Ver usuarios</b>. Para traer la lista más reciente del reloj usa <b>Actualizar del reloj</b> (corre en segundo plano).</td></tr></tbody>
                    </table>
                </div>
                <div class="usr-fila mt-2">
                    <b id="usrSeleccion">0 seleccionados</b>
                    <button type="button" class="btn btn-success btn-sm" id="btnUsrAlta" title="Crea al empleado en el reloj con su número y nombre; después solo se registra el rostro en el reloj"><i class="fa fa-user-plus"></i> Dar de alta en el reloj</button>
                    <span class="usr-copiar">
                        <button type="button" class="btn btn-primary btn-sm" id="btnUsrCopiar" title="Copia el rostro HACIA el reloj de arriba (&quot;Reloj:&quot;), tomándolo del reloj que elijas aquí"><i class="fa fa-clone"></i> Copiar rostro a este reloj</button>
                        <span style="font-size:12.5px;">desde:</span>
                        <select class="custom-select custom-select-sm" id="usrOrigen" style="width:auto;"></select>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnUsrCopiarTodo" title="Igual que Copiar rostro, pero ADEMÁS crea en este reloj a los que no tienen rostro (solo número y nombre), tomándolos del reloj elegido"><i class="fa fa-clone"></i> + sin rostro</button>
                        <label class="mb-0 text-nowrap" style="font-size:12px;" title="Si el usuario YA existe en este reloj, lo ACTUALIZA con el rostro y nombre del reloj origen (para corregir altas equivocadas de RH). Si no se marca, a los que ya tienen rostro no se les toca."><input type="checkbox" id="usrReemplazar"> Reemplazar si ya existe</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnUsrCopiarVarios" title="Copia TODOS los usuarios de ESTE reloj (el de arriba, 'Reloj:') hacia varios relojes a la vez, en segundo plano"><i class="fa fa-sitemap"></i> a varios relojes…</button>
                    </span>
                    <span id="usrAvance" style="font-size:13px;"></span>
                    <!-- Quitar (destructivo) se separa a la orilla derecha para no confundirlo con Copiar -->
                    <button type="button" class="btn btn-danger btn-sm" id="btnUsrQuitar" style="margin-left:auto;" title="Solo empleados dados de baja o que no existen; los activos nunca se quitan"><i class="fa fa-user-times"></i> Quitar del reloj</button>
                </div>
                <div class="text-muted mt-2" style="font-size:12px;">
                    <i class="fa fa-info-circle"></i> <b>Dar de alta</b> crea al empleado sin rostro: después en el reloj solo se le registra el rostro.
                    <b>Copiar rostro</b> pasa al empleado con su rostro desde otro reloj BioStar 2 (no se toca a quien ya tiene rostro en este reloj).
                    El FaceGo no permite altas sin rostro (ahí se crean al registrar el rostro en el reloj).
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<!-- Modal: copiar del reloj principal a varios relojes -->
<div class="modal fade" id="modalCopiarVarios" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-sitemap"></i> Copiar a varios relojes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Copia <b>todos los usuarios</b> del reloj que tienes arriba en <b>"Reloj:"</b><br>
                   <b>Principal (origen):</b> <span id="cvOrigen" class="text-primary"></span></p>
                <p class="mb-1">A los relojes marcados:</p>
                <div id="cvDestinos" style="max-height:220px; overflow:auto; border:1px solid #cfd6de; border-radius:4px; padding:8px;"></div>
                <div class="mt-2">
                    <label class="mb-1 d-block" style="font-size:13px;" title="Si el usuario ya existe en el reloj destino, lo actualiza con el rostro y nombre del principal."><input type="checkbox" id="cvReemplazar"> Reemplazar si ya existe (actualizar)</label>
                    <label class="mb-0 d-block" style="font-size:13px;" title="Además de los rostros, crea a los que no tienen rostro (solo número y nombre)."><input type="checkbox" id="cvIncluir"> Incluir usuarios sin rostro</label>
                    <label class="mb-0 d-block mt-2" style="font-size:13px; color:#c62828;" title="CUIDADO: borra TODOS los usuarios del reloj destino y luego los vuelve a dar de alta desde el principal. Deja el reloj idéntico al principal."><input type="checkbox" id="cvBorrar"> <b>Borrar todo primero</b> y luego dar de alta (deja el destino idéntico al principal)</label>
                </div>
                <div class="text-muted mt-2" style="font-size:12px;"><i class="fa fa-info-circle"></i> Corre en segundo plano, un reloj a la vez. El avance se ve en la barra de arriba. No cierres el modal de relojes si quieres seguirlo.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btnCvCopiar"><i class="fa fa-clone"></i> Copiar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal confirmación para quitar usuarios del reloj -->
<div class="modal fade" id="modalQuitar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#c62828; color:#fff;">
                <h5 class="modal-title" style="color:#fff;"><i class="fa fa-exclamation-triangle"></i> Quitar usuarios del reloj</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Se van a <b>quitar <span id="quitarCuantos"></span> usuario(s)</b> del reloj <b id="quitarReloj"></b>, con su rostro.</p>
                <p style="font-size:13px;">Solo se quitan empleados dados de baja o que no existen en el sistema; si alguno está activo, el sistema lo protege y no lo quita. Si después regresa, habrá que darlo de alta y registrar su rostro otra vez (o copiarlo de otro reloj).</p>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label style="font-weight:bold!important;">Usuario administrador</label>
                        <input type="text" class="form-control" id="quitarUsuario" autocomplete="off">
                    </div>
                    <div class="form-group col-6">
                        <label style="font-weight:bold!important;">Contraseña</label>
                        <input type="password" class="form-control" id="quitarClave" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight:bold!important;">Para confirmar escribe <span class="text-danger">QUITAR</span></label>
                    <input type="text" class="form-control" id="quitarConfirma" autocomplete="off" placeholder="QUITAR">
                </div>
                <div id="quitarMensaje" class="text-danger" style="font-size:13px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarQuitar"><i class="fa fa-user-times"></i> Sí, quitar del reloj</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: borrar los relojes destino antes de copiar (muy peligroso: pide clave de administrador) -->
<div class="modal fade" id="modalCopiaBorrar" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#c62828; color:#fff;">
                <h5 class="modal-title" style="color:#fff;"><i class="fa fa-exclamation-triangle"></i> Borrar y reemplazar (requiere administrador)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff;"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Vas a <b>BORRAR TODOS los usuarios</b> de <b><span id="cbCuantos"></span> reloj(es) destino</b> y volver a darlos de alta desde el principal. <b>Esto no se puede deshacer.</b></p>
                <p style="font-size:13px;">El reloj <b>principal</b> (origen) <u>no se toca</u>; solo se vacían los relojes destino que marcaste. Aun así, por ser una operación peligrosa, se necesita la clave de un <b>administrador</b>.</p>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label style="font-weight:bold!important;">Usuario administrador</label>
                        <input type="text" class="form-control" id="cbUsuario" autocomplete="off">
                    </div>
                    <div class="form-group col-6">
                        <label style="font-weight:bold!important;">Contraseña</label>
                        <input type="password" class="form-control" id="cbClave" autocomplete="new-password">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-weight:bold!important;">Para confirmar escribe <span class="text-danger">BORRAR</span></label>
                    <input type="text" class="form-control" id="cbConfirma" autocomplete="off" placeholder="BORRAR">
                </div>
                <div id="cbMensaje" class="text-danger" style="font-size:13px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarCopiaBorrar"><i class="fa fa-trash"></i> Sí, borrar y copiar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal historial de comunicaciones -->
<div class="modal fade" id="modalHistorialCom" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-list"></i> Historial de comunicaciones con relojes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body" style="max-height:65vh; overflow-y:auto;">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Fecha</th><th>Reloj</th><th>Acción</th><th>Usuario</th><th>Autorizó</th><th>Resultado</th></tr></thead>
                    <tbody id="comHistorial"></tbody>
                </table>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<style>
/* El tema oculta los checkbox sueltos (left:-9999px); en estas listas se usan los nativos */
#modalComunicacion input[type="checkbox"], #modalUsuarios input[type="checkbox"], #modalCopiarVarios input[type="checkbox"] {
    position: static !important; left: auto !important; opacity: 1 !important; visibility: visible !important;
    width: 16px; height: 16px; margin: 0 6px 0 0; vertical-align: middle; cursor: pointer; accent-color: #1e88e5;
}
#modalCopiarVarios #cvDestinos label { display: block; margin: 0 0 4px 0; cursor: pointer; }
#modalComunicacion .com-accion, #modalComunicacion .com-lista tbody tr { cursor: pointer; }
#modalComunicacion .com-titulo { display: block; width: 100%; font-weight: 600; font-size: 13px; margin-bottom: 4px; }
#modalComunicacion .modal-dialog { max-width: 1100px; width: 94vw; }
#modalComunicacion .com-lista { border: 1px solid #cfd6de; border-radius: 4px; max-height: 62vh; overflow-y: auto; }
#modalComunicacion .com-lista td, #modalComunicacion .com-lista th { white-space: nowrap; }
#modalComunicacion .com-lista td, #modalComunicacion .com-lista th { font-size: 13px; vertical-align: middle; }
#modalComunicacion .com-acciones { padding: 8px 10px; }
#modalComunicacion .com-accion { display: block; padding: 6px 4px; margin: 0; cursor: pointer; border-bottom: 1px solid rgba(138,150,163,.25); }
#modalComunicacion .com-accion small { margin-left: 20px; font-size: 11.5px; }
#modalComunicacion .com-peligro { color: #c62828; font-weight: 600; }
#modalComunicacion .com-peligro small { color: #c62828; font-weight: 400; }
#modalComunicacion .com-prox { font-size: 12px; padding: 8px 4px 2px; }
#modalComunicacion .com-avance { border: 1px solid #cfd6de; border-radius: 4px; max-height: 30vh; overflow-y: auto; }
#modalComunicacion .com-avance td { font-size: 12.5px; }
#modalComunicacion tr.com-sel { background: rgba(30,136,229,.12); }
#modalUsuarios .usr-fila { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
#modalUsuarios .usr-tabla { border: 1px solid #cfd6de; border-radius: 4px; max-height: 52vh; overflow-y: auto; }
#modalUsuarios .usr-tabla td, #modalUsuarios .usr-tabla th { font-size: 12.5px; vertical-align: middle; padding: 3px 6px; }
#modalUsuarios .usr-tabla thead th { position: sticky; top: 0; background: #f4f6f8; z-index: 1; }
#modalUsuarios .usr-filtro { cursor: pointer; font-size: 12.5px; padding: 5px 9px; }
#modalUsuarios .usr-filtro.activo { box-shadow: 0 0 0 2px #1e88e5; }
#modalUsuarios .usr-orden { cursor: pointer; user-select: none; white-space: nowrap; }
#modalUsuarios .usr-orden:hover { color: #1e88e5; }
#modalUsuarios .usr-flecha { opacity: .35; margin-left: 3px; font-size: 11px; }
#modalUsuarios .usr-flecha.fa-sort-up, #modalUsuarios .usr-flecha.fa-sort-down { opacity: 1; color: #1e88e5; }
/* Catálogo de relojes: letra chica legible; cada renglón en una línea salvo Conexión (multilínea).
   La tabla hace scroll horizontal cuando hace falta. */
#tablaRelojes td, #tablaRelojes th { white-space: nowrap; font-size: 12px; vertical-align: middle; }
#tablaRelojes td.estadoConexion { white-space: normal; min-width: 130px; max-width: 180px; }
#tablaRelojes td.estadoConexion .badge { white-space: normal; display: inline-block; }
#tablaRelojes td:nth-child(7), #tablaRelojes th:nth-child(7) { font-size: 11px; }   /* Planta respaldo más chica */
html.modo-nocturno #modalUsuarios .modal-content,
html.modo-nocturno #modalQuitar .modal-content,
html.modo-nocturno #modalCopiaBorrar .modal-content { background: #23272e; color: #dfe3e8; }
html.modo-nocturno #modalUsuarios td, html.modo-nocturno #modalUsuarios th { color: #e8ecf1; border-color: #3a4150; }
html.modo-nocturno #modalUsuarios .usr-tabla { border-color: #3a4150; }
/* Botones "outline" se ven con muy poco contraste en modo oscuro: letra y borde claros */
html.modo-nocturno #btnUsrCopiarTodo { color: #8ab4f8 !important; border-color: #4a6fa5 !important; }
html.modo-nocturno #btnUsrCopiarVarios { color: #cbd2da !important; border-color: #5b6673 !important; }
#usrCopiaProgreso { border: 1px solid #cfd6de; border-radius: 4px; background: #f4f8fc; }
#usrCopiaProgreso, #usrCopiaProgreso b, #usrCopiaTexto { color: #1a2733; }
html.modo-nocturno #usrCopiaProgreso { background: #222935; border-color: #3a4150; }
html.modo-nocturno #usrCopiaProgreso, html.modo-nocturno #usrCopiaProgreso b,
html.modo-nocturno #usrCopiaTexto, html.modo-nocturno #usrCopiaNums { color: #e8ecf1 !important; }
html.modo-nocturno #modalUsuarios .usr-tabla thead th { background: #2b3038; }
html.modo-nocturno #modalUsuarios .text-muted, html.modo-nocturno #modalQuitar .text-muted,
html.modo-nocturno #modalCopiaBorrar .text-muted { color: #9aa4b0 !important; }
html.modo-nocturno #modalComunicacion .modal-content,
html.modo-nocturno #modalBorrado .modal-content,
html.modo-nocturno #modalHistorialCom .modal-content { background: #23272e; color: #dfe3e8; }
html.modo-nocturno #modalComunicacion td, html.modo-nocturno #modalComunicacion th,
html.modo-nocturno #modalHistorialCom td, html.modo-nocturno #modalHistorialCom th { color: #e8ecf1; border-color: #3a4150; }
html.modo-nocturno #modalComunicacion .com-lista, html.modo-nocturno #modalComunicacion .com-avance { border-color: #3a4150; }
html.modo-nocturno #modalComunicacion .com-peligro, html.modo-nocturno #modalComunicacion .com-peligro small { color: #ef9a9a; }
html.modo-nocturno #modalComunicacion .text-muted, html.modo-nocturno #modalBorrado .text-muted { color: #9aa4b0 !important; }
</style>

<script>var RELOJES_ES_ADMIN = <?php echo $esAdmin ? 'true' : 'false'; ?>;</script>
<script src="assets/js/relojes.js?v=<?php echo filemtime(FCPATH.'assets/js/relojes.js'); ?>"></script>
