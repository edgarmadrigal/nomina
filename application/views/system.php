<style>
.morris-hover.morris-default-style {
    border-radius: 4px;
    padding: 10px 12px;
    top: -110px!important;
    color: #666;
    background: #263238;
    border: none;
    color: #f8f9fa!important;
    box-shadow: none;
    font-size: 14px;
}
#area-chart,
#line-chart,
#bar-chart,
#stacked,
#pie-chart{
  min-height: 250px;
}
</style>

    <!-- chartist CSS -->
    <link href="assets/node_modules/morrisjs/morris.css" rel="stylesheet">
    <link href="assets/css/pages/dashboard1.css" rel="stylesheet">

        <!-- ============================================================== -->
        <!-- Page wrapper  -->
        <!-- ============================================================== -->
        <div class="page-wrapper">
            <!-- ============================================================== -->
            <!-- Container fluid  -->
            <!-- ============================================================== -->
            <div class="container-fluid">
                <!-- ============================================================== -->
                <!-- Bread crumb and right sidebar toggle -->
                <!-- ============================================================== -->
                <div class="row page-titles">
                    <div class="col-md-5 align-self-center">
                        <h3 class="text-themecolor">Reportes</h3>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                        </ol>
                    </div>
                </div>
                <!-- ============================================================== -->
                <!-- End Bread crumb and right sidebar toggle -->
                <!-- ============================================================== -->
                <!-- ============================================================== -->
                <!-- Sales Chart and browser state-->
                <!-- ============================================================== -->
                <div class="row">
                    <!-- Column -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex no-block">
                                    <div>
                                        <h5 class="card-title m-b-0">Reporte de Asistencia</h5>
                                    </div>
                                    <div class="ml-auto">
                                        <ul class="list-inline text-center font-12">
                                            <li><i class="fa fa-circle text-success"></i>Retardos</li>
                                            <li><i class="fa fa-circle text-info"></i> Faltas</li>
                                            <li><i class="fa fa-circle text-warning"></i> Asistencias</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="" id="sales-chart" style="height: 355px;"></div>
                            </div>
                        </div>
                    </div>
                    <!-- Column -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex m-b-30 no-block">
                                    <h5 class="card-title m-b-0 align-self-center">Our Visitors</h5>
                                    <div class="ml-auto">
                                        <select class="custom-select b-0">
                                            <option selected="">Today</option>
                                            <option value="1">Tomorrow</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="visitor" style="height:260px; width:100%;"></div>
                                <ul class="list-inline m-t-30 text-center font-12">
                                    <li><i class="fa fa-circle text-purple"></i> Tablet</li>
                                    <li><i class="fa fa-circle text-success"></i> Desktops</li>
                                    <li><i class="fa fa-circle text-info"></i> Mobile</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ============================================================== -->
                <!-- End Sales Chart -->
            </div>
            <!-- ============================================================== -->
            <!-- End Container fluid  -->       
    <script src="assets/node_modules/raphael/raphael-min.js"></script>
    <script src="assets/node_modules/morrisjs/morris.min.js"></script>

        <script src="assets/js/dashboard1.js"></script>