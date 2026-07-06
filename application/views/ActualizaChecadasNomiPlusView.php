
<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<script src="assets/js/jquery.blockUI.js"></script>
<!-- ============================================================== -->
<!-- Page wrapper ESTILO1  9876543210 123-->
<!-- ============================================================== -->
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Importa de Nomiplus a la Base de Datos</h4> 
                <div class="card">
                    <div class="card-body">     
                            <div class="">
                                <div class="row">
                                    
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Planta</label>
                                            <select class="custom-select col-12" id="id_empresa" required>
                                                <option value="" disabled selected>Selecciona una planta</option>
                                                <option value="1" > ACABADOS_ESPECIALES				</option>
                                                <option value="2" > ADMINISTRATIVO_MMS				</option>
                                                <option value="3" > CORTE							</option>
                                                <option value="4" > DESARROLLO						</option>
                                                <option value="5" > IBAMENSUAL						</option>
                                                <option value="6" > LAVANDERIA						</option>
                                                <option value="7" > PLANCHAS						</option>
                                                <option value="8" > PROCESOS_SECOS					</option>
                                                <option value="9" > SEGUNDAS						</option>
                                                <option value="10"> TERMINADO						</option>
                                                <option value="11"> NAZARENO1						</option>
                                                <option value="12"> NAZARENO2						</option>
                                                <option value="13"> NAZARENO3						</option>
                                                <option value="14"> CUENCAME						</option>
                                                <option value="15"> PILAR							</option>
                                                <option value="16"> DTL3							</option>
                                                <option value="17"> DTL								</option>
                                                <option value="18"> DTLNOM2							</option>
                                                <option value="19"> ADMINISTRATIVO_TBG				</option>
                                                <option value="20"> CUENCAME_CROM					</option>
                                                <option value="21"> PEDRICEÑA						</option>
                                                <option value="22"> SYSEL_NOM						</option>
                                                <option value="25"> EVENTUALES						</option>
                                                <option value="26"> LEON_GUZMAN						</option>
                                                <option value="27"> TCT								</option>
                                                <option value="28"> LEON_GUZMAN_ALMOHADAS			</option>		
                                                <option value="29"> DTLNOM_MENS						</option>			
                                                <option value="30"> ARCINAS							</option>			
                                                <option value="32"> TRANSPORTELAGOMA_NOM			</option>			
                                                <option value="33"> DELTA2							</option>			
                                                <option value="34"> DELTA3							</option>			
                                                <option value="35"> DTLNOM							</option>			
                                                <option value="36"> DTLNOM_MENS						</option>							
                                                <option value="37"> DTLNOM2_						</option>			
                                                <option value="38"> DTL3_							</option>			
                                                <option value="39"> TLAHUALILO						</option>			
                                                <option value="40"> DELTA2							</option>			
                                                <option value="41"> TBG								</option>	                                        
                                            </select>
                                        </div>
                                    </div>     
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
                                                <option  value="2019">2019</option>
                                                <option value="2020">2020</option>
                                                <option value="2021">2021</option>
                                                <option  value="2022">2022</option>
                                                <option  value="2023">2023</option>
                                                <option value="2024">2024</option>
                                                <option value="2025">2025</option>
                                                <option selected value="2026">2026</option>                                                
                                                <option value="2027">2027</option>
                                                <option value="2028">2028</option>
                                                <option value="2029">2029</option>
                                                <option value="2030">2030</option>
                                                <option value="2031">2031</option>
                                                <option value="2032">2032</option>
                                            </select>
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

		
