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
                            <a id="descargar" class="btn btn-info"  href="http://<?= ip_publica() ?>:8087/nomina/reportX_1.csv" download>
                                <i class="fa fa-download"></i> Descargar
                            </a>
                            <div class="col-3">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;">Planta</label>
                                    <select class="custom-select col-12" id="planta" required></select>
                                </div>
                            </div> 
                            Subir archivo de excel : 
                            <input  type="file" name="uploadFile" value="" class="col-md-10 archivo"/><br><br>
                            <input id="subir" type="button" name="submit" value="Subir" class="col-md-12" />                            
                        </form>                        
                    </div>
                </div>
            </div>
        </div>        
        <pre id="resultadoImport" style="display:none; max-height:400px; overflow:auto; background:#f7f7f7; padding:10px; margin-top:15px;"></pre>
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
<script src="assets/js/importar.js"></script>