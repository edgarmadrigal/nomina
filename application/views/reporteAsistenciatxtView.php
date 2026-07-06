
<meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>
<script src="assets/js/jquery.blockUI.js"></script>

<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Reporte Asistencia Txt</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="row">
                            
                                    <div class="col-2" >
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">No Semana</label>
                                            <input id="NoSemana" type="text" class="form-control" name="msg" placeholder="escriba NoSemana" >
                                        </div>
                                    </div>                   
                            
                            <div class="col-2" >
                                <div class="form-group">
                                    <label style="font-weight: bold!important;">Planta</label>
                                     <select class="custom-select col-12" id="planta" required>
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
                            <div class="col-2">
                                <div class="form-group">
                                    <label style="font-weight: bold!important;"> &nbsp; </label><br> 
                                    <button id="buscar" class="btn btn-info">
                                        <i class="fa fa-download"></i> Descargar fichero
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/reporteAsistenciatxt.js"></script>
