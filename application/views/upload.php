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
    .archivo{
        /*width: 520px;*/
        height: 220px;
    }
</style>
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row" id="tablaEmp">
            <div class="col-12">
                <h4 class="card-title">Importar Archivo Excel de checadas BioStar a Base de Datos</h4> 
                <div class="card">
                    <div class="card-body">     
                        <form>
                            FORMATO ESPERADO:
                            <a id="descargar" class="btn btn-info"  href="http://177.244.42.94:8087/nomina/reportX_1.csv" download>
                                <i class="fa fa-download"></i> Descargar
                            </a>
                            <BR>
                            <BR>
                            <BR>
                            Subir archivo de excel : 
                            <input  type="file" name="uploadFile" value="" class="col-md-10 archivo"/><br><br>
                            <input id="subir" type="button" name="submit" value="Subir" class="col-md-12" />
                            
                        </form>
                        
                    </div>
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
    </div>
</div>

<script src="assets/js/jquery.blockUI.js"></script>
<script>
    $(document).ready(function () {
            $(document).on('click', '#subir', function (e) {
                $.blockUI({ message: '<h1><img src="assets/js/busy.gif" /> Procesando...</h1>' });
            $("#tablaEmp").css("display", "none");
            $(".loadTable").css("display", "block");  
                var data = new FormData(jQuery('form')[0]);
                jQuery.ajax({
                    url: 'importar/uploadData',
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    method: 'POST',
                    type: 'POST', // For jQuery < 1.9
                    success: function(data){
                        alert(data);
                        $.unblockUI();
                        $("#tablaEmp").css("display", "block");
                        $(".loadTable").css("display", "none");
                    }
                });
            });
    });
</script>