<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<meta charset="UTF-8">
<link href='assets/css/select2.min.css' rel='stylesheet' type='text/css'>
<script src='assets/js/select2.min.js'></script>
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<!-- 111719 SYSDBA NORBERTO
AinNR19
(CurrentDate-Empleados['FECHA_NACIMIENTO'])/365;
LAJAT0025
dr[0].ToString()                    po
/////
dr[7].ToString().Substring(10,2)    talla izq

dr[7].ToString().Substring(12,2)    talla der

dr[7].ToString().Substring(10, 4);  tallas

/////////////////////////
dr[7].ToString().Substring(0,6)     estilo  
.Replace('-','')
////////////////////////
dr[7].ToString()                    cantidad

////////////////////

dr[7].ToString()                    UPC

.Replace('-','')

.Replace(' ','')
--->

<style>
    @media only screen and (min-width: 1000px) {
        .table-responsive {
            overflow-x: visible;
            -ms-overflow-style: -ms-autohiding-scrollbar;
        }
    }
    .table-responsive {
        overflow-x: auto;
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
<script src="assets/js/jquery.blockUI.js"></script>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Asistencia</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">   
                            <div class="row">                            
                                    <div class="col-sm-8 col-sm-push-4">
                                    </div>
                                    <div class="col-sm-2 col-sm-pull-4">
                                        <button id="actualizarTodo" class="btn btn-success">
                                            Actualizar Todo  <i class="fa fa-refresh"></i>
                                        </button>
                                    </div>
                                    <div class="col-sm-2 col-sm-pull-4">
                                        <button id="actualizar" class="btn btn-success">
                                            Actualizar  <i class="fa fa-refresh"></i>
                                        </button>
                                    </div>
                            </div>
                                <div class="row">       
                                <div class="col-2" >
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Semana</label>
                                            <input id="NoSemana" type="text" class="form-control" name="msg" placeholder="escriba NoSemana" >
                                        </div>
                                    </div>                                    
                                    <div class="col-2" >
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Año</label>
                                            <select class="custom-select col-12" id="anio">
                                                <option value="2019">2018</option>
                                                <option selected value="2019">2019</option>
                                                <option value="2020">2020</option>
                                                <option value="2021">2021</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta</label>
                                            <select class="custom-select col-12" id="planta">
                                            </select>
                                        </div>
                                    </div> 
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Departamento</label>
                                            <select class="custom-select col-12" id="departamento">
                                            </select>
                                        </div>
                                    </div> 
                                </div> 
                                <div class="row">
                                    <div class="col-4">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Puesto</label>
                                            <select class="custom-select col-12" id="puesto">
                                            </select>
                                        </div>
                                    </div> 
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Empleado</label>
                                            <input id="noempleado" type="text" class="form-control" name="msg" placeholder="escriba NoEmpleado">
                                        </div>
                                    </div>  
                                    <div class="col-2">
                                        <div class="form-group">
                                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                                    <button id="buscar" class="btn btn-info">
                                                        <i class="fa fa-search"></i> Buscar
                                                    </button>
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
</div>  

<script src="assets/js/asistencia.js"></script>
<script src="assets/js/calendarioEsp.js"></script>

<script>
    $(function () {
        $("#fechaInicio").datepicker({
            //endDate: '0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
            //daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
            firstDay: 1
        }).datepicker('setDate', new Date());
        $( "#fechaInicio" ).datepicker();

        $("#fechaFin").datepicker({
            //endDate: '0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
            //daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
            firstDay: 1
        }).datepicker('setDate', new Date());

        
        $( "#fechaFin" ).datepicker();
    });  

</script>
<style>
.dataTables_wrapper {
    padding-top: 10px;
    overflow: hidden;
}
@media only screen and (min-width: 1000px) {
    .table-responsive {
        overflow-x: visible;
        -ms-overflow-style: -ms-autohiding-scrollbar;
    }
}
    .table-responsive {
        overflow-x: auto;
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