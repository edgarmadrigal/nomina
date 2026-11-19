<?php
$e = $estado;
$h = function ($v) { return htmlspecialchars((string) $v); };
if (!empty($e['mensaje'])) { $badge = 'secondary'; $txt = $e['mensaje']; }
elseif ($e['vigente'] && $e['indefinida']) { $badge = 'success'; $txt = 'Activa (indefinida)'; }
elseif ($e['vigente']) { $badge = $e['diasRestantes'] <= $avisoDias ? 'warning' : 'success'; $txt = 'Activa hasta ' . substr($e['venceEl'], 0, 10) . ' (' . $e['diasRestantes'] . ' días)'; }
elseif (!empty($e['serieCambio'])) { $badge = 'danger'; $txt = 'Inactiva: el servidor o la base de datos cambió, se necesita un código nuevo'; }
else { $badge = 'danger'; $txt = 'Inactiva'; }
?>
<style>
#licencia .lic-serie { font: 600 28px/1.2 monospace; letter-spacing: 2px; color: #1e5aa8; }
#licencia .lic-dato small { display: block; color: #888; }
#licencia #licCodigo { font: 600 20px monospace; text-transform: uppercase; letter-spacing: 2px; max-width: 220px; }
body.dark-mode #licencia .lic-serie, .dark #licencia .lic-serie { color: #8cb8ff; }
</style>
<div class="page-wrapper">
    <div class="container-fluid" id="licencia">
        <div class="row"><div class="col-12">
            <h4 class="card-title"><i class="fa fa-key"></i> Licencia del sistema</h4>
            <div class="card"><div class="card-body">
                <div class="row">
                    <div class="col-md-5 lic-dato mb-3"><small>Número de serie</small><span class="lic-serie"><?php echo $h($e['serie']); ?></span></div>
                    <div class="col-md-4 lic-dato mb-3"><small>Estado</small><span class="badge badge-<?php echo $badge; ?>" style="font-size:15px"><?php echo $h($txt); ?></span>
                        <?php if (isset($e['requerida']) && !$e['requerida']): ?><br><small>Esta instalación no requiere licencia: no se bloquea ninguna función.</small><?php endif; ?></div>
                    <div class="col-md-3 lic-dato mb-3"><small>Servidor / base</small><?php echo $h(isset($e['servidor']) ? $e['servidor'] . ' / ' . $e['base'] : ''); ?></div>
                </div>
                <?php if (!empty($e['activadaEl'])): ?><p class="text-muted mb-1">Última activación: <?php echo $h($e['activadaEl']); ?></p><?php endif; ?>
                <?php if (!empty($e['solicitudFecha'])): ?><p class="text-muted mb-1">Solicitud pendiente desde <?php echo $h($e['solicitudFecha']); ?> (<?php echo $h($e['solicitudPor']); ?>)</p><?php endif; ?>
                <p class="text-muted mb-0">Sin licencia vigente se deshabilitan algunas funciones (prenómina, TXT, importaciones y costos). Consultar, entrar al sistema y la descarga de los relojes siguen funcionando, y no se borra ninguna información.</p>
            </div></div>

            <?php if ($esAdmin && !empty($e['mensaje'])): ?>
            <div class="card"><div class="card-body">
                <p>Las tablas de licencia aún no existen. Créalas y activa la licencia (indefinida) con un clic:</p>
                <button type="button" class="btn btn-success" id="licInstalar"><i class="fa fa-database"></i> Crear tablas y activar (indefinida)</button>
                <div id="licMsg" class="mt-3"></div>
            </div></div>
            <?php endif; ?>

            <?php if ($esAdmin && empty($e['mensaje'])): ?>
            <div class="card"><div class="card-body">
                <h5>Activar / desactivar</h5>
                <p>1. Solicita el código: se envía un correo al proveedor con el número de serie y un código por cada opción de tiempo.</p>
                <button type="button" class="btn btn-info mb-3" id="licSolicitar"><i class="fa fa-envelope"></i> Solicitar código</button>
                <p>2. Captura el código que te proporcionen:</p>
                <div class="form-inline">
                    <input type="text" id="licCodigo" class="form-control mr-2" placeholder="XXXXX-XXXXX" maxlength="11" autocomplete="off">
                    <button type="button" class="btn btn-success" id="licAplicar"><i class="fa fa-check"></i> Aplicar código</button>
                </div>
                <div id="licMsg" class="mt-3"></div>
            </div></div>

            <div class="card"><div class="card-body">
                <h5>Movimientos</h5>
                <div class="table-responsive"><table class="table table-sm">
                    <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalle</th></tr></thead>
                    <tbody>
                    <?php foreach ($movimientos as $m): ?>
                        <tr><td><?php echo $h($m['fecha']); ?></td><td><?php echo $h($m['usuario']); ?></td><td><?php echo $h($m['accion']); ?></td><td><?php echo $h($m['detalle']); ?></td></tr>
                    <?php endforeach; if (!$movimientos): ?><tr><td colspan="4" class="text-muted">Sin movimientos</td></tr><?php endif; ?>
                    </tbody>
                </table></div>
            </div></div>
            <?php endif; ?>
        </div></div>
    </div>
</div>
<?php if ($esAdmin): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var msg = function (ok, t) { $('#licMsg').html($('<div class="alert">').addClass(ok ? 'alert-success' : 'alert-danger').text(t)); };
    var enviar = function (btn, url, datos) {
        btn.prop('disabled', true);
        $.post(url, datos || {}, function (r) { msg(r.exito, r.mensaje); if (r.exito) setTimeout(function () { location.reload(); }, 1500); }, 'json')
         .fail(function () { msg(false, 'No se pudo completar la operación.'); })
         .always(function () { btn.prop('disabled', false); });
    };
    $('#licInstalar').on('click', function () {
        if (confirm('Se crearán las tablas de licencia y quedará activa de forma indefinida. ¿Continuar?')) enviar($(this), 'licencia/instalar');
    });
    $('#licSolicitar').on('click', function () {
        if (confirm('Se enviará una solicitud nueva y los códigos anteriores dejarán de servir. ¿Continuar?')) enviar($(this), 'licencia/solicitar');
    });
    $('#licAplicar').on('click', function () {
        var c = $.trim($('#licCodigo').val());
        if (c) enviar($(this), 'licencia/aplicar', { codigo: c });
    });
});
</script>
<?php endif; ?>
