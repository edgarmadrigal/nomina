<!-- Inicio del perfil Invitados (home.php): solo la música, sin tablero de checadas ni asistencias -->
<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card mt-4 text-center">
                    <div class="card-body" style="padding:36px 20px;">
                        <i class="fa fa-music" style="font-size:56px;color:#1e88e5;"></i>
                        <h3 class="mt-3">Hola, <?php echo htmlspecialchars($user->nombre); ?></h3>
                        <p class="text-muted" style="font-size:15px;">Aquí puedes escuchar la radio y música de YouTube, y platicar por el chat.</p>
                        <button type="button" class="btn btn-primary btn-lg" id="invitadoMusica" style="color:#fff !important;">
                            <i class="fa fa-play-circle"></i> Abrir la música
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $('#invitadoMusica').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();   // el panel se cierra al hacer clic fuera de él
        if (window.Radio && Radio.fnAbrir) Radio.fnAbrir(); else $('#radioIcono').trigger('click');
    });
});
</script>
