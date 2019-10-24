
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Empleados</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">
                                <div class="row">              
                                    <div class="col-md-12 text-right">
                                        <div class="messages"></div>
                                        <button id="actualizar" class="btn btn-warning" >
                                            <i class="fa fa-refresh"></i> Actualizar Empleados Microsip</button><!-- fa-spin -->
                                       <!-- </button>  -->
                                    </div> 
                                </div>      
                                <div class="row" >
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta</label>
                                            <select class="custom-select col-12 required" id="planta" required>
                                            </select>
                                        </div>
                                    </div>
                                </div>   
                                
                                <div class="row" class="loadTable">
                                           <div class="col-12" style="text-align: -webkit-center;">
                                            <center>
                                            <div class="loadTable" ></div>                                        
                                            </center>
                                        </div>
                                </div>
                                <div class="row" id="tablaEmpleados">
                                    <div class="col-12">
                                        <table id="empleados" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%;display:inline-table;">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <!--  <th>EMPRESA</th> -->
                                                    <th>EMPLEADO_ID</th>
                                                    <!--  <th>NUMERO</th>-->
                                                    <!--  <th>NO_BYTE</th>-->
                                                    <th>NOMBRE_COMPLETO</th>
                                                    <th>PUESTO</th>
                                                    <th>DEPARTAMENTO</th>
                                                   <!-- <th>RFC</th>-->
                                                   <!-- <th>REG_IMSS</th>-->
                                                   <!-- <th>INGRESO</th>-->
                                                    <th>ESTATUS</th>
                                                   <!-- <th>OPCIONES</th>-->
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>
                                                    <!--  <th>EMPRESA</th> -->
                                                    <th>EMPLEADO_ID</th>
                                                    <!--  <th>NUMERO</th>-->
                                                    <!--  <th>NO_BYTE</th>-->
                                                    <th>NOMBRE_COMPLETO</th>
                                                    <th>PUESTO</th>
                                                    <th>DEPARTAMENTO</th>
                                                   <!-- <th>RFC</th>-->
                                                   <!-- <th>REG_IMSS</th>-->
                                                   <!-- <th>INGRESO</th>-->
                                                    <th>ESTATUS</th>
                                                   <!-- <th>OPCIONES</th>-->
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
 
<script src="assets/js/empleados.js"></script>

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