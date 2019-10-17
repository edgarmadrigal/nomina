
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Conceptos</h4> 
                <div class="card">
                    <div class="card-body">     
                        <div class="table-responsive">    
                            <div class="dataTables_wrapper dt-bootstrap4">    
                                <div class="row">              
                                    <div class="col-md-12 text-right">
                                        <div class="messages"></div>
                                        <button id="nuevo" class="btn btn-info" data-toggle="modal" data-target="#myModal_agregar" >
                                            <i class="fa fa-plus-circle"></i> Agregar concepto</button>
                                        </button>  
                                    </div> 
                                </div>      
                                <div class="row">
                                    <div class="col-12">
                                        <table id="example" class="display nowrap table table-hover table-striped table-bordered dataTable" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>     
                                                    <th>Concepto</th>
                                                    <th>Descripcion Corta</th>
                                                    <th>Clasificacion</th>
                                                    <th>Con Goce de Sueldo</th>
                                                    <th>Incluir Comentario</th>                                           
                                                    <th>Opciones</th>
                                                </tr>
                                            </thead>
                                            <tfoot>
                                                <tr>
                                                    <th>ID</th>     
                                                    <th>Concepto</th>
                                                    <th>Descripcion Corta</th>
                                                    <th>Clasificacion</th>
                                                    <th>Con Goce de Sueldo</th>
                                                    <th>Incluir Comentario</th>                                           
                                                    <th>Opciones</th>
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
    <div class="modal fade" id="myModal_agregar" tabindex="-1" role="dialog" aria-labelledby="myModal_categoriasLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
                    <h4 class="modal-title" id="myModal_categoriasLabel">AGREGAR</h4></div>
                <div class="modal-body">
                    <div class="container">      
                    <form id="formulario" class="cmxform" method="get" action="">                               
                        <div class="row">
                            <div class="col-md-12">
                                <input id="id" type="text" style="display:none;">
                               
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Concepto:</label> 
                                                    <input required type="text" class="form-control" value="" id="descripcion" maxlength="40" >
                                        </div>
                                    </div>
                                </div>                                  
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label style="font-weight: bold!important;">Descripcion Corta:</label> 
                                                    <input required type="text" class="form-control" style="text-transform: uppercase"  value="" id="descripcionCorta" maxlength="4" >
                                        </div>
                                    </div>
                                </div>  
                                
                                <div class="row">
                                    <div class="col-md-12">                            
                                        <div class="form-group">
                                                <label style="font-weight: bold!important;">Clasificacion</label>
                                                <select class="custom-select col-12 required" id="clasificacion" required>
                                                </select>
                                            </div>
                                    </div>
                                </div> 
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="controls" style="WIDTH: 146%;MARGIN: inherit;"> <!--- style="WIDTH: 146%;MARGIN: inherit;"> -->
                                            <label class="custom-control custom-checkbox">
                                                <input id="goceSueldo" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Goce de Sueldo</span> </label>
                                                <div class="help-block">
                                                </div>
                                            </div>                                        
                                        </div>
                                    </div>
                                </div> 

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="controls" style="WIDTH: 146%;MARGIN: inherit;"> <!--- style="WIDTH: 146%;MARGIN: inherit;"> -->
                                            <label class="custom-control custom-checkbox">
                                                <input id="comentarios" type="checkbox" value="single" name="styled_single_checkbox" 
                                                class="custom-control-input" aria-invalid="false">
                                                <span class="custom-control-label">Incluir Comentarios</span> </label>
                                                <div class="help-block">
                                                </div>
                                            </div>                                        
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </form>
                    </div>    
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    <button id="btn_guardar" class="submit btn btn-success" type="button" value="Submit">Guardar</button> 
                </div>
            </div>
        </div>
    </div>
<script src="assets/js/conceptos.js"></script>
