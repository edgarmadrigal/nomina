
    <meta charset="UTF-8">
<link href="assets/css/datepicker.css" rel="stylesheet" type="text/css" />
<script src="assets/js/bootstrap-datepicker.js"></script>

<script src="assets/js/jquery.blockUI.js"></script>
<div class="page-wrapper">   
    <div class="container-fluid">        
    <title>Subir y procesar CSV</title>  
        
     <style>
        table { border-collapse: collapse; width: 80%; }
        td, th { border: 1px solid #999; padding: 6px; }
        th { background: #eee; }
        .btn-azul {
            background-color: #007bff; color: white; border: none;
            padding: 10px 20px; cursor: pointer; border-radius: 4px; font-size: 16px;
        }
        .btn-azul:hover { background-color: #0056b3; }
        .btn-verde {
            background-color: #28a745; color: white; border: none;
            padding: 10px 20px; cursor: pointer; border-radius: 4px; font-size: 16px;
            text-decoration: none;
        }
        .btn-verde:hover { background-color: #1e7e34; }
    </style>
    <h2>Subir archivo CSV</h2>

    <?php if (isset($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <!-- Form con acción correcta según base_url -->
    <?php echo form_open_multipart('reporte/procesar_csv'); ?>
        <input type="file" name="archivo_csv" accept=".csv" required>
        <br><br>
        <button type="submit" class="btn-azul">📤 Subir CSV</button>
    </form>

    <?php if (isset($lineas) && count($lineas) > 0): ?>
        <h2>Previsualización del archivo procesado</h2>
        <p>👉 En las líneas <strong>1,3,5...</strong> se eliminaron las comas
        <table>
            <tbody>
                <?php foreach ($lineas as $fila): ?>
                    <tr>
                        <?php 
                            $cols = str_getcsv($fila); // respeta comillas y comas internas
                            foreach ($cols as $col):
                        ?>
                            <td><?php echo htmlspecialchars($col); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <!-- Botón de descarga sin recargar página -->
        <button class="btn-verde" onclick="descargarCSV()">📥 Descargar CSV</button>
    <?php endif; ?>

<script>
function descargarCSV() {
    fetch('http://192.168.128.248:8080/nomina/reporte/descargar_csv')
        .then(resp => resp.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            // Nombre dinámico del archivo
            a.download = 'reporte_' + new Date().toISOString().slice(0,10) + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(err => {
            alert('Error al descargar el archivo.');
            console.error(err);
        });
}
</script>
    </div>
</div>