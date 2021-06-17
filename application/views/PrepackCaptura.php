<!-- ============================================================== -->
<!-- Page wrapper  -->
<!-- ============================================================== -->


<link href='assets/css/relojCSS.css' rel='stylesheet' type='text/css'>
<script src="assets/js/relojJS.js"></script>
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>
<script src="assets/js/calendarioEsp.js"></script>
<style>
.modal-header{
    display:none;
}
</style>
<div class="page-wrapper">   
    <div class="container-fluid">           
        <div class="row">
            <div class="col-12">
                <h4 class="card-title">Captura de Prepack</h4> 
                <div class="card">
                    <div class="card-body">
                         Contrato:  <input type="text" placeholder="Contrato"    class="form-control col-3">
                         Estilo:    <input type="text" placeholder="Estilo"    class="form-control col-3">
                         PrePack:   <input type="text" placeholder="PrePack"    class="form-control col-3">
                         <button id="btn_guardar" class="submit btn btn-success" type="submit" value="Submit">Guardar</button> 
                        <div class="table-responsive">   
                        
                        <table id="faqs" class="table table-hover">
                                 <thead>
                                     <tr>
                                         <th>Talla</th>
                                         <th>Cantidad</th>
                                         <th>Codigo</th>
                                         <th></th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <tr>
                                         <td><input type="text" placeholder="Talla"    class="form-control"></td>
                                         <td><input type="text" placeholder="Cantidad" class="form-control"></td>                   
                                         <td><input type="text" placeholder="Codigo"  class="form-control" maxlength="15" onkeypress='return validaNumericos(event)' ></td>  
                                         <td class="mt-10">&nbsp</td>
                                     </tr>
                                 </tbody>
                             </table>
                        </div> <div class="text-center"><button onclick="addfaqs();" class="badge badge-success"><i class="fa fa-plus"></i> ADD NEW</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 


   <!-- <script src="assets/js/usuarios.js"></script>-->

<script>    
    function validaNumericos(event) {
        if(event.charCode >= 48 && event.charCode <= 57){
        return true;
        }
        return false;
    }
        
        var faqs_row = 0;
    function addfaqs() {
    html = '<tr id="faqs-row' + faqs_row + '">';
        html += '<td><input type="text" class="form-control" placeholder="Talla"></td>';
        html += '<td><input type="text" placeholder="Cantidad" class="form-control"></td>';
        html += '<td><input type="text" placeholder="Codigo" class="form-control" maxlength="15" onkeypress="'+ validaNumericos(event)+'"></td>';
        html += '<td class="mt-10"><button class="badge badge-danger" onclick="$(\'#faqs-row' + faqs_row + '\').remove();"><i class="fa fa-trash"></i> Delete</button></td>';
        html += '</tr>';
    $('#faqs tbody').append(html);
    faqs_row++;
    }
</script>

<style>
    body {
        background-color: #f9f9fa
    }

    .flex {
        -webkit-box-flex: 1;
        -ms-flex: 1 1 auto;
        flex: 1 1 auto
    }

    @media (max-width:991.98px) {
        .padding {
            padding: 1.5rem
        }
    }

    @media (max-width:767.98px) {
        .padding {
            padding: 1rem
        }
    }

    .padding {
        padding: 5rem
    }

    .card {
        box-shadow: none;
        -webkit-box-shadow: none;
        -moz-box-shadow: none;
        -ms-box-shadow: none
    }

    .pl-3,
    .px-3 {
        padding-left: 1rem !important
    }

    .card {
        position: relative;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid #d2d2dc;
        border-radius: 0
    }

    .card .card-title {
        color: #000000;
        margin-bottom: 0.625rem;
        font-size: 0.875rem;
        font-weight: 500
    }

    .card .card-description {
        margin-bottom: .875rem;
        font-weight: 400;
        color: #76838f
    }

    p {
        font-size: 0.875rem;
        margin-bottom: .5rem;
        line-height: 1.5rem
    }

    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        -ms-overflow-style: -ms-autohiding-scrollbar
    }

    .table,
    .jsgrid .jsgrid-table {
        width: 100%;
        max-width: 100%;
        margin-bottom: 1rem;
        background-color: transparent
    }

    .table thead th,
    .jsgrid .jsgrid-table thead th {
        border-top: 0;
        border-bottom-width: 1px;
        font-weight: 500;
        font-size: .875rem;
        text-transform: uppercase
    }

    .table td,
    .jsgrid .jsgrid-table td {
        font-size: 0.875rem;
        padding: .475rem 0.4375rem
    }

    .mt-10 {
        padding: 0.875rem 0.3375rem !important
    }

    button {
        outline: 0 !important
    }

    .form-control:focus {
        box-shadow: 0 0 0 0rem rgba(0, 123, 255, .25) !important
    }

    .badge {
        border-radius: 0;
        font-size: 12px;
        line-height: 1;
        padding: .375rem .5625rem;
        font-weight: normal;
        border: none
    }
</style>