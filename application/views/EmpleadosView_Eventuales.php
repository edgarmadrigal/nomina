<!-- ============================================================== -->
<!-- EmpleadosView_Eventuales -->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Empleados Eventuales</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">    
                                <div class="row">              
                                    <div class="col-md-12 text-right">
                                        <div class="messages"></div>
                                        <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#myModal_agregar" >
                                            <i class="fa fa-plus-circle"></i> Agregar empleado</button>
                                        </button>  
                                    </div> 
                                </div>      
                                <div class="row">
                                    <div class="col-12">
                                        <table id="example" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>NoEmpleado</th>
                                                    <th>NombreCompleto</th>
                                                    <th>NombrePuesto</th>
                                                    <th>NombreDepartamento</th>
                                                    <th>Estatus</th>
                                                    <th>Opciones</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>NoEmpleado</th>
                                                    <th>NombreCompleto</th>
                                                    <th>NombrePuesto</th>
                                                    <th>NombreDepartamento</th>
                                                    <th>Estatus</th>
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
                                        <label style="font-weight: bold!important;">Numero de Empleado:</label> 
                                        <input id="NUMERO" type="text" class="form-control required" name="msg" placeholder="Escribe el numero de empleado" required>
                                    </div>
                                </div>
                            </div>   
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Nombres:</label> 
                                        <input id="NOMBRES" type="text" class="form-control required" name="msg" placeholder="Escribe los nombres" required>
                                    </div>
                                </div>
                            </div>   
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Apellido Paterno:</label> 
                                        <input id="APELLIDO_PATERNO" type="text" class="form-control required" name="msg" placeholder="Escribe el Apellido Paterno" required>
                                    </div>
                                </div>
                            </div> 
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Apellido Materno:</label> 
                                        <input id="APELLIDO_MATERNO" type="text" class="form-control required" name="msg" placeholder="Escribe el Apellido Materno" required>
                                    </div>
                                </div>
                            </div> 
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Nombre del Puesto:</label> 
                                        <input id="NOMBRE_PUESTO" type="text" class="form-control required" name="msg" placeholder="Escribe el Puesto" required>
                                    </div>
                                </div>
                            </div> 
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label style="font-weight: bold!important;">Nombre del Departamento:</label> 
                                        <input id="NOMBRE_DEPARTAMENTO" type="text" class="form-control required" name="msg" placeholder="Escribe el Departamento" required>
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

    <script src="assets/js/empleados_Eventuales.js"></script>


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