<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h4 class="card-title"><i class="fa fa-music"></i> Música</h4>
                <div class="card" style="max-width: 560px;">
                    <div class="card-body">
                        <h5>Contraseña de la música</h5>
                        <p style="font-size: 13px;">
                            Es la contraseña que pide el símbolo de música (junto al nombre del usuario) para escuchar la radio.
                            Es <b>independiente</b> de la contraseña del administrador: cambiarla no cambia tu contraseña para entrar al sistema.
                        </p>
                        <p style="font-size: 13px;">
                            <?php if (!empty($info)) { ?>
                                <span class="badge badge-success" style="color:#fff !important;"><i class="fa fa-check"></i> Definida</span>
                                Último cambio: <?php echo htmlspecialchars($info['fecha']); ?>
                                <?php if ($info['usuarioModifico']) echo 'por ' . htmlspecialchars($info['usuarioModifico']); ?>
                            <?php } else { ?>
                                <span class="badge badge-warning" style="color:#fff !important;"><i class="fa fa-exclamation"></i> Sin definir</span>
                                Nadie puede usar la radio hasta que la definas.
                            <?php } ?>
                        </p>
                        <div class="form-group">
                            <label style="font-weight: bold!important;">Nueva contraseña</label>
                            <input type="password" class="form-control" id="musicaNueva" autocomplete="new-password" placeholder="Mínimo 4 caracteres">
                        </div>
                        <div class="form-group">
                            <label style="font-weight: bold!important;">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="musicaConfirmar" autocomplete="new-password">
                        </div>
                        <button type="button" class="btn btn-success" id="musicaGuardar" style="color:#fff !important;"><i class="fa fa-save"></i> Guardar contraseña</button>
                    </div>
                </div>
                <div class="card" style="max-width: 560px;">
                    <div class="card-body">
                        <h5><i class="fa fa-youtube-play" style="color:#e62117;"></i> YouTube en el panel de música</h5>
                        <p style="font-size: 13px;">
                            Para buscar canciones de YouTube hace falta una <b>clave de Google</b> (YouTube Data API v3, gratis).
                            Se pueden hacer unas <b>100 búsquedas nuevas al día</b>; las que se repiten se guardan 7 días y no cuentan.
                        </p>
                        <p style="font-size: 13px;">
                            <?php if (!empty($youtube)) { ?>
                                <span class="badge badge-success" style="color:#fff !important;"><i class="fa fa-check"></i> Configurada</span>
                                (termina en <b>…<?php echo htmlspecialchars($youtube['final']); ?></b>) ·
                                <?php echo htmlspecialchars($youtube['fecha']); ?>
                                <?php if ($youtube['usuarioModifico']) echo 'por ' . htmlspecialchars($youtube['usuarioModifico']); ?>
                            <?php } else { ?>
                                <span class="badge badge-warning" style="color:#fff !important;"><i class="fa fa-exclamation"></i> Sin clave</span>
                                Solo funciona la radio.
                            <?php } ?>
                        </p>
                        <details style="font-size: 13px; margin-bottom: 12px;">
                            <summary style="cursor:pointer; font-weight:bold;">¿Cómo saco la clave?</summary>
                            <ol style="padding-left: 18px; margin-top: 8px;">
                                <li>Entra a <a href="https://console.cloud.google.com/" target="_blank" rel="noopener">console.cloud.google.com</a> con una cuenta de Google (puede ser la de la empresa).</li>
                                <li>Arriba, en el selector de proyectos, da <b>Proyecto nuevo</b>, ponle de nombre <i>Nominas Musica</i> y <b>Crear</b>.</li>
                                <li>Con ese proyecto elegido, ve a <a href="https://console.cloud.google.com/apis/library/youtube.googleapis.com" target="_blank" rel="noopener">YouTube Data API v3</a> y da <b>Habilitar</b>.</li>
                                <li>Ve a <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Credenciales</a> › <b>Crear credenciales</b> › <b>Clave de API</b>. Copia la clave (empieza con <code>AIza</code>).</li>
                                <li>Recomendado: en esa clave, <b>Restricciones de API</b> › <i>Restringir clave</i> › marca solo <b>YouTube Data API v3</b> › Guardar.</li>
                                <li>Pégala aquí abajo y da <b>Guardar clave</b> (se prueba antes de guardarse).</li>
                            </ol>
                            No pide tarjeta ni cobra nada mientras no se activen pagos en el proyecto.
                        </details>
                        <div class="form-group">
                            <label style="font-weight: bold!important;">Clave de API de YouTube</label>
                            <input type="password" class="form-control" id="youtubeClave" autocomplete="off" placeholder="AIza...">
                        </div>
                        <button type="button" class="btn btn-success" id="youtubeGuardar" style="color:#fff !important;"><i class="fa fa-save"></i> Guardar clave</button>
                        <?php if (!empty($youtube)) { ?>
                            <button type="button" class="btn btn-danger" id="youtubeQuitar" style="color:#fff !important;"><i class="fa fa-trash"></i> Quitar</button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {
    $('#musicaGuardar').on('click', function () {
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: 'musica/cambiarClave',
            type: 'post',
            data: { nueva: $('#musicaNueva').val(), confirmar: $('#musicaConfirmar').val() },
            dataType: 'json'
        }).done(function (r) {
            if (typeof swal === 'function') swal(r.exito ? 'Listo' : 'Revisa', r.mensaje, r.exito ? 'success' : 'warning');
            else alert(r.mensaje);
            if (r.exito) setTimeout(function () { location.reload(); }, 1200);
        }).fail(function () {
            alert('No se pudo guardar la contraseña.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    function guardarYoutube(clave, $btn) {
        $btn.prop('disabled', true);
        $.ajax({
            url: 'musica/guardarYoutube',
            type: 'post',
            data: { clave: clave },
            dataType: 'json'
        }).done(function (r) {
            if (typeof swal === 'function') swal(r.exito ? 'Listo' : 'Revisa', r.mensaje, r.exito ? 'success' : 'warning');
            else alert(r.mensaje);
            if (r.exito) setTimeout(function () { location.reload(); }, 1200);
        }).fail(function () {
            alert('No se pudo guardar la clave.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }
    $('#youtubeGuardar').on('click', function () {
        var clave = $.trim($('#youtubeClave').val());
        if (!clave) { alert('Pega la clave de YouTube.'); return; }
        guardarYoutube(clave, $(this));
    });
    $('#youtubeQuitar').on('click', function () {
        if (confirm('¿Quitar la clave de YouTube? Solo quedará la radio.')) guardarYoutube('', $(this));
    });
});
</script>
