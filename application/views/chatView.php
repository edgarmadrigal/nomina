<div class="page-wrapper">
    <div class="container-fluid">
        <div class="chat-app" id="chatApp" data-admin="<?php echo $esAdmin ? '1' : '0'; ?>">
            <!-- Lista de conversaciones -->
            <div class="chat-lista">
                <div class="chat-lista-cab">
                    <h4 class="mb-0"><i class="fa fa-comments"></i> Chat</h4>
                    <div class="chat-acciones">
                        <button type="button" class="btn btn-sm btn-primary" id="chatBtnNuevo" style="color:#fff !important;"><i class="fa fa-plus"></i> Nuevo</button>
                        <button type="button" class="btn btn-sm btn-warning" id="chatBtnFalla" style="color:#fff !important;"><i class="fa fa-bug"></i> Reportar falla</button>
                        <button type="button" class="btn btn-sm btn-info" id="chatBtnBurbuja" style="color:#fff !important;" title="Ver el chat en un circulito flotante en todas las pantallas"><i class="fa fa-comment"></i> Abrir en burbuja</button>
                    </div>
                </div>
                <div class="chat-filtros">
                    <a href="javascript:void(0)" class="chat-filtro activo" data-filtro="">Todas</a>
                    <a href="javascript:void(0)" class="chat-filtro" data-filtro="SOPORTE">Fallas</a>
                    <a href="javascript:void(0)" class="chat-filtro" data-filtro="NOLEIDOS">No leídos</a>
                </div>
                <input type="text" class="form-control form-control-sm chat-buscar" id="chatBuscar" placeholder="Buscar conversación">
                <div class="chat-conversaciones" id="chatConversaciones">
                    <div class="chat-vacio"><i class="fa fa-spinner fa-spin"></i></div>
                </div>
            </div>

            <!-- Conversación abierta -->
            <div class="chat-panel" id="chatPanel">
                <div class="chat-panel-cab">
                    <button type="button" class="chat-regresar" id="chatRegresar" title="Regresar"><i class="fa fa-arrow-left"></i></button>
                    <div class="chat-panel-titulo">
                        <div id="chatTitulo"><b>Elige una conversación</b></div>
                        <small id="chatSubtitulo" class="text-muted"></small>
                    </div>
                    <select id="chatEstado" class="custom-select custom-select-sm" style="display:none; width:auto;">
                        <option value="ABIERTO">Abierto</option>
                        <option value="EN PROCESO">En proceso</option>
                        <option value="RESUELTO">Resuelto</option>
                    </select>
                    <span id="chatEstadoBadge" class="badge" style="display:none;"></span>
                </div>
                <div class="chat-mensajes" id="chatMensajes">
                    <div class="chat-vacio">
                        <i class="fa fa-comments-o" style="font-size:40px;"></i><br>
                        Escribe a otro usuario con <b>Nuevo</b>, platica en <b>Nóminas (todos)</b><br>
                        o usa <b>Reportar falla</b> si algo del sistema no funciona.
                    </div>
                </div>
                <div class="chat-escribir" id="chatEscribir" style="display:none;">
                    <div id="chatAdjunto" class="chat-adjunto" style="display:none;"></div>
                    <div class="chat-escribir-fila">
                        <label class="chat-clip" title="Adjuntar imagen (también puedes pegar una captura con Ctrl+V)">
                            <i class="fa fa-paperclip"></i>
                            <input type="file" id="chatArchivo" accept="image/*" style="display:none;">
                        </label>
                        <textarea id="chatTexto" class="form-control" rows="1" maxlength="4000" placeholder="Escribe un mensaje (Enter envía, Shift+Enter nueva línea)"></textarea>
                        <button type="button" class="btn btn-primary" id="chatEnviar" style="color:#fff !important;"><i class="fa fa-paper-plane"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Nueva conversación -->
<div class="modal fade" id="modalChatNuevo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-plus"></i> Nueva conversación</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="chatTipoNuevo" id="chatTipoDirecto" value="DIRECTO" checked>
                        <span class="custom-control-label">Con una persona</span>
                    </label>
                    <label class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" name="chatTipoNuevo" id="chatTipoGrupo" value="GRUPO">
                        <span class="custom-control-label">Grupo</span>
                    </label>
                </div>
                <input type="text" class="form-control mb-2" id="chatGrupoTitulo" maxlength="150" placeholder="Nombre del grupo" style="display:none;">
                <input type="text" class="form-control form-control-sm mb-1" id="chatBuscarUsuario" placeholder="Buscar usuario">
                <div class="chat-usuarios" id="chatUsuarios"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="chatCrear" style="color:#fff !important;"><i class="fa fa-comments"></i> Iniciar</button>
            </div>
        </div>
    </div>
</div>

<!-- Reportar falla -->
<div class="modal fade" id="modalChatFalla" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-bug"></i> Reportar una falla o detalle del sistema</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px;">Le llega a los administradores; aquí mismo te responden y puedes ver si ya quedó resuelto.</p>
                <div class="form-group">
                    <label style="font-weight: bold!important;">¿Qué pasó? (título corto)</label>
                    <input type="text" class="form-control" id="fallaTitulo" maxlength="150" placeholder="Ej. No descarga el reporte de asistencia">
                </div>
                <div class="form-group">
                    <label style="font-weight: bold!important;">Pantalla donde pasó</label>
                    <input type="text" class="form-control" id="fallaPagina" maxlength="300" placeholder="Ej. Reporte de asistencia TXT">
                </div>
                <div class="form-group">
                    <label style="font-weight: bold!important;">Descripción</label>
                    <textarea class="form-control" id="fallaTexto" rows="4" maxlength="4000" placeholder="Qué estabas haciendo, qué esperabas y qué salió (mensaje de error, semana, planta...)"></textarea>
                </div>
                <div class="form-group mb-0">
                    <label style="font-weight: bold!important;">Captura de pantalla <small class="text-muted">(opcional; puedes pegarla con Ctrl+V)</small></label>
                    <input type="file" class="form-control" id="fallaArchivo" accept="image/*">
                    <div id="fallaAdjunto" class="chat-adjunto mt-1" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="fallaEnviar" style="color:#fff !important;"><i class="fa fa-paper-plane"></i> Enviar reporte</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/chat.js?v=<?php echo filemtime(FCPATH.'assets/js/chat.js'); ?>"></script>
