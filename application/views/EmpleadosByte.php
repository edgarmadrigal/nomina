<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Empleados Byte</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">   
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta</label>
                                            <select class="custom-select col-12 required" id="empresa" required>
                                                <option value="1" selected="selected">Nazareno 1</option>
                                                <option value="2">Nazareno 2</option>
                                                <option value="3">Nazareno 3</option>
                                                <option value="4">Cuencamé</option>
                                            </select>
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
                                <div class="row" id="tablaEmp" >
                                    <div class="col-12">
                                        <table id="example" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>NO_BYTE</th>
                                                    <th>NUMERO DE EMPLEADO</th>
                                                    <th>N_COMPLETO</th>
                                                    <th>AP_PATERNO</th>
                                                    <th>AP_MATERNO</th>
                                                    <th>NOMBRES</th>
                                                    <th>ESTATUS</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>NO_BYTE</th>
                                                    <th>NUMERO DE EMPLEADO</th>
                                                    <th>N_COMPLETO</th>
                                                    <th>AP_PATERNO</th>
                                                    <th>AP_MATERNO</th>
                                                    <th>NOMBRES</th>
                                                    <th>ESTATUS</th>
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

<script src="assets/js/empleadoByte.js"></script>
<style>
    @media only screen and (min-width: 1000px) {
        .table-responsive {
            overflow-x: visible;
            -ms-overflow-style: -ms-autohiding-scrollbar;
        }
    }
    @media only screen and (max-width: 1000px) {
        .table-responsive {
            overflow-x: auto;
        }
    }

    .loadTable {
    border: 16px solid #f3f3f3;
    border-radius: 50%;
    border-top: 16px solid #3498db;
    width: 120px;
    height: 120px;
    -webkit-animation: spin 2s linear infinite; /* Safari */ 
    animation: spin 2s linear infinite;
    }

    /* Safari */
    @-webkit-keyframes spin {
    0% { -webkit-transform: rotate(0deg); }
    100% { -webkit-transform: rotate(360deg); }
    }

    @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
    }
</style>