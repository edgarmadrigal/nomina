
<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<script src="assets/js/jquery.blockUI.js"></script>
<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           ñ
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Importa de Nomiplus las Checadas Invalidas de Eventuales a la Base de Datos</h4> 
                <div class="card">
                    <div class="card-body">     
                            <div class="">
                                <div class="row">
                                    <div class="col-3">
                                        <label style="font-weight: bold!important;">FechaInicio</label> 
                                        <div id="datepicker" class="input-group date" data-date-format="yyyy/mm/dd">
                                            <input id="fecha1" class="form-control" type="text" readonly />
                                            <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                                        </div>
                                    </div>                                   
                                    <div class="col-3">
                                        <label style="font-weight: bold!important;">FechaFin</label> 
                                        <div id="datepicker2" class="input-group date" data-date-format="yyyy/mm/dd">
                                            <input id="fecha2" class="form-control" type="text" readonly />
                                            <span class="input-group-addon"><div data-icon="ei-calendar"></div></span>
                                        </div>
                                    </div>      
                                    <div class="col-3">
                                        <label style="font-weight: bold!important;">Importar</label> 
                                        <div class="messages"></div>
                                        <button id="buscar" class="btn btn-warning form-control" >
                                            <i class="fa fa-refresh"></i> Importar Checadas </button><!-- fa-spin -->
                                       <!-- </button>  -->
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
 

<!--<script src="assets/js/jquery.blockUI.js"></script> -->
<script src="assets/js/ActualizaChecadasNomiplus.js"></script>


<script>

    $(function () {
        $("#datepicker").datepicker({
           //endDate: '-0d',
            //showOnFocus :'true',
           // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
           // daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy/mm/dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
    }); 
    
    
    $(function () {
        $("#datepicker2").datepicker({
           //endDate: '-0d',
            //showOnFocus :'true',
           // startDate: '-0d',  // ESTABLECE FECHA MINIMA HOY /// BLOQUEA DIAS ANTERIORES MINDATE minDate
           // daysOfWeekDisabled: [0,2,3,4,5, 6], 
            dateFormat: "yyyy/mm/dd",
            autoclose: true, 
            todayHighlight: true, 
            language: 'es',
        }).datepicker('update', new Date());
    });  

</script>

		
