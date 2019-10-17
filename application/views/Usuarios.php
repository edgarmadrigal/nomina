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
                                                    <th>Opciones</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nombre</th>
                                                    <th>Usuario</th>
                                                    <th>Perfil</th>
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

    <script src="assets/js/usuarios.js"></script>

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