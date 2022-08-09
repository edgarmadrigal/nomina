<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<script src="assets/js/jquery.blockUI.js"></script>
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Reporte Diario</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">   
                                <div class="row">                                   
                                    <div class="col-4">
                                        <label style="font-weight: bold!important;">Fecha</label> 
                                        <div id="datepicker" class="input-group date" data-date-format="yyyy-mm-dd">
                                            <input id="fecha" class="form-control" type="text" readonly />
                                            <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                                        </div>
                                    </div>
                                    <div class="col-4" >
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta</label>
                                            <select class="custom-select col-12" id="planta">
                                            </select>
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
                                    
                                    <div class="col-2">
                                        <div class="form-group">
                                                        <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                            <button id="actualizarTodo" class="btn btn-success">
                                                Actualizar Todo  <i class="fa fa-refresh"></i>
                                            </button>
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
                                <div class="row" id="tablaLunes">
                                    <div class="col-12">
                                        <table id="diario" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>EMP. PLANTA</th>
                                                    <th>EMP. MATUTINO</th>
                                                    <th>EMP. NOCTURNO</th>
                                                    <th>ASIS. MATUTINO</th>
                                                    <th>ASIS. NOCTURNO</th>
                                                    <th>FALT. MATUTINO</th>
                                                    <th>FALT. NOCTURNO</th>
                                                    <th>RET. MATUTINO</th>
                                                    <th>RET. NOCTURNO</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>EMP. PLANTA</th>
                                                    <th>EMP. MATUTINO</th>
                                                    <th>EMP. NOCTURNO</th>
                                                    <th>ASIS. MATUTINO</th>
                                                    <th>ASIS. NOCTURNO</th>
                                                    <th>FALT. MATUTINO</th>
                                                    <th>FALT. NOCTURNO</th>
                                                    <th>RET. MATUTINO</th>
                                                    <th>RET. NOCTURNO</th>
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

<link href='assets/css/select2.min.css' rel='stylesheet' type='text/css'>
<script src='assets/js/select2.min.js'></script>
<style>
    .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
        color: #ffffff!important;
        background-color: #2f3d4a!important;
        border-color: #20aee3 #20aee3 #007bff!important;
    }
    .custom-control {
        margin-bottom: -24px!important;
    }
    .select2-container--default .select2-results>.select2-results__options {
        max-height: 428px!important;
        overflow-y: auto;
    }
    #horarioLunes{
        font-size:15px;
    }
</style>
<script src="assets/js/diarioChecadas.js"></script>
<script src="assets/js/calendarioEsp.js"></script>
<script>

    $(function () {
        $("#fecha").datepicker({
           //endDate: '-0d',
            //showOnFocus :'true',
           // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
           // daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy-mm-dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
    });  

</script>

		
