<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Usuarios</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">    
                                <div class="row">              
                                    <div class="col-md-12 text-right">
                                        <div class="messages"></div>
                                        <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#myModal_agregar" >
                                            <i class="fa fa-plus-circle"></i> Agregar usuario</button>
                                        </button>  
                                    </div> 
                                </div>      
                                <div class="row">
                                    <div class="col-12">
                                        <table id="example" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th>Usuario</th>
                                                    <th>Perfil</th>
                                                    <th>Planta</th>                                                    
                                                    <th>Opciones</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th>Usuario</th>
                                                    <th>Perfil</th>
                                                    <th>Planta</th>   
                                                    <th>Opciones</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>    
    <!-- ============================================================== -->    
    <!-- End Container fluid  -->
    <!-- ============================================================== -->
    <div class="modal fade" id="myModal_agregar" tabindex="-1" role="dialog" aria-labelledby="myModal_categoriasLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="myModal_categoriasLabel">AGREGAR</h4></div>
                <div class="modal-body">
                    <div class="container">
                        <form id="formulario" class="cmxform" method="get" action="">
                        <fieldset>
                            <input id="id" type="text" style="display:none;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Nombre:</label> 
                                        <input id="nombre" type="text" class="form-control required" name="msg" placeholder="Nombre" required>
                                    </div>
                                </div>
                            </div>                        
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Usuario:</label> 
                                        <input id="usuario" type="text" class="form-control required" name="msg" placeholder="usuario" required>
                                    </div>
                                </div>
                            </div>                        
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Contraseña:</label> 
                                        <input id="password" type="password"  class="form-control required" name="msg" placeholder="constraseña" required>
                                        <div class="checkbox m-t-20">
                                            <input id="drop-remove" type="checkbox" onclick="verPassword()">
                                            <label for="drop-remove" class="col-md-12">
                                                ver Contraseña
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">                            
                                    <div class="form-group">
                                            <label style="font-weight: bold!important;">Perfil</label>
                                            <select class="custom-select col-12 required" id="perfil" required>
                                            </select>
                                        </div>
                                </div>
                            </div> 
                            <div class="row">
                                <div class="col-md-12">                            
                                    <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta principal</label>
                                            <select class="custom-select col-12 required" id="planta" required>
                                            </select>
                                        </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 up-bloque">
                                    <label style="font-weight: bold!important;">Plantas que puede ver</label>
                                    <div class="up-modos">
                                        <button type="button" class="btn up-op activo" data-modo="todas"><i class="fa fa-building"></i> Todas las plantas</button>
                                        <button type="button" class="btn up-op" data-modo="elegir"><i class="fa fa-check-square-o"></i> Elegir plantas</button>
                                    </div>
                                    <div class="up-lista" style="display:none;">
                                        <div class="up-barra">
                                            <input type="text" class="form-control up-buscar" placeholder="Buscar planta">
                                            <span class="up-cuenta"></span>
                                        </div>
                                        <div class="up-items"></div>
                                    </div>
                                    <small class="text-muted up-ayuda">La planta principal siempre se incluye. Es lo mismo que "Empresas" en Permisos.</small>
                                </div>
                            </div> 
                            </fieldset>
                        </form>                        
                    </div>
                </div>
                <div class="modal-footer">
                     <!--<button id="btn_guardar" type="button" class="btn btn-success">Aceptar</button>-->
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    <button id="btn_guardar" class="submit btn btn-success" type="submit" value="Submit">Guardar</button> 
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar contraseña -->
    <div class="modal fade" id="modalPassword" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-key"></i> Cambiar contraseña</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="passUsuarioId">
                    <p>Usuario: <b id="passUsuarioNombre"></b></p>
                    <p class="text-muted" style="font-size:13px;">La contraseña anterior no se puede ver porque está cifrada. Escribe una nueva y pásasela al usuario.</p>
                    <div class="form-group">
                        <label style="font-weight:bold!important;">Contraseña nueva</label>
                        <input type="password" class="form-control" id="passNueva" placeholder="mínimo 8 caracteres" autocomplete="new-password">
                    </div>
                    <div class="form-group">
                        <label style="font-weight:bold!important;">Repite la contraseña</label>
                        <input type="password" class="form-control" id="passRepite" autocomplete="new-password">
                    </div>
                    <div class="checkbox">
                        <input id="passVer" type="checkbox"> <label for="passVer">ver contraseña</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarPassword"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Permisos -->
    <div class="modal fade" id="modalPermisos" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document" style="max-width:1000px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permisosTitulo">Permisos</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="permisosIdUsuario">
                    <div class="row align-items-end mb-2">
                        <div class="col-6">
                            <label style="font-weight:bold!important;">Copiar permisos de otro usuario</label>
                            <select class="custom-select" id="permisosCopiarDe"></select>
                        </div>
                        <div class="col-6 text-right">
                            <small class="text-muted">Los cambios se aplican la próxima vez que el usuario cambie de pantalla.</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-7" id="permisosOpciones" style="max-height:480px; overflow-y:auto;"></div>
                        <div class="col-5" style="max-height:480px; overflow-y:auto;">
                            <h6 style="font-weight:bold; border-bottom:1px solid #ddd; padding-bottom:4px;">Empresas que puede consultar</h6>
                            <label class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="permisosTodasEmpresas">
                                <span class="custom-control-label"><b>Todas las empresas</b></span>
                            </label>
                            <div id="permisosEmpresas"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarPermisos">Guardar permisos</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/usuarios.js?v=<?php echo filemtime(FCPATH.'assets/js/usuarios.js'); ?>"></script>
<style>
#myModal_agregar .up-bloque { margin-bottom: 10px; }
#myModal_agregar .up-bloque > label { display: block !important; float: none !important; width: 100%; }
#myModal_agregar .up-modos { display: flex; flex-wrap: wrap; gap: 6px; margin: 4px 0; }
#myModal_agregar .up-op { border: 1px solid #1e88e5; color: #1e88e5; background: transparent; font-size: 13px; padding: 4px 10px; }
#myModal_agregar .up-op.activo { background: #1e88e5; color: #fff !important; }
#myModal_agregar .up-barra { display: flex; align-items: center; gap: 10px; margin: 6px 0 4px; font-size: 13px; }
#myModal_agregar .up-barra .up-buscar { flex: 1 1 auto; height: 34px; }
#myModal_agregar .up-cuenta { font-weight: bold; white-space: nowrap; }
#myModal_agregar .up-items { max-height: 220px; overflow-y: auto; border: 1px solid rgba(128,128,128,.35); border-radius: 4px; padding: 4px 8px; }
#myModal_agregar .up-item { display: flex !important; align-items: center; float: none !important; width: 100%; margin: 0; padding: 3px 0; font-weight: normal; cursor: pointer; border-bottom: 1px solid rgba(128,128,128,.15); }
#myModal_agregar .up-item.up-oculto { display: none !important; }
#myModal_agregar .up-item.up-principal { opacity: .7; }
#myModal_agregar .up-item input[type=checkbox] { position: static !important; left: auto !important; opacity: 1 !important; visibility: visible !important; display: inline-block !important; width: auto !important; height: auto !important; flex: 0 0 auto; margin: 0 6px 0 0; }
#myModal_agregar .up-ayuda { display: block; font-size: 12px; }
</style>
    <script src="assets/js/usuariosPermisos.js?v=<?php echo filemtime(FCPATH.'assets/js/usuariosPermisos.js'); ?>"></script>

    <script>
        
    function verPassword() {
        var x = document.getElementById("password");
        if (x.type === "password") {
            x.type = "text";
        } else {
            x.type = "password";
        }
    }    
    </script>

<style>
@media only screen and (min-width: 600px) {
    .table-responsive {
        overflow-x: visible;
        -ms-overflow-style: -ms-autohiding-scrollbar;
    }
}
@media only screen and (max-width: 600px) {
    .table-responsive {
        overflow-x: auto;
    }
}
</style>