<html>
<head>
<?php header('Access-Control-Allow-Origin: *');  ?>
<style type="text/css">
        hr {
        page-break-after: always;
        border: 0;
        margin: 0;
        padding: 0;
        }
        table {
          width:100%!important;
        }
        table, th, td {
          border: 1px solid black;
          border-collapse: collapse;
        }
        thead{
          font-size: 11px!important;
          display:table-header-group;
        }
        tfoot{
          display:table-row-group;
        }
        tr{
          page-break-inside:avoid;
        }
        th, td {
          padding: .5px;
          text-align: left;
        }
        table#t01 tr:nth-child(even) {
          background-color: #eee;
        }
        table#t01 tr:nth-child(odd) {
          background-color: #fff;
        }
        table#t01 th {
          background-color: black;
          color: white;
        }
        html {
          margin: 0;
          font-size: 10px!important;
        }
        body {
          font-size: 10px!important;
        }
        .titulo{
          font-weight: bold;
          float:right;
          text-align: right;
        }
        .alinearDerecha{
          font-size: 10px!important;
          float:right;
          text-align: right;
        }
        .ReporteAsistencia{
          font-size:18px!important;
          text-align: right;
        }
        .subtotal{
          border-collapse: separate!important;
          border: 0px solid white!important;
          font-weight: bold;
          float:right;
          text-align: right;
        }
        .total{
          border-collapse: separate!important;
          border: 1px solid white!important;
          font-weight: bold;
          text-align: right;
          font-size:12px!important;
          border-bottom-color: black;
          border-bottom-style: solid;
          border-bottom-width: 1px;
        }
        .fecha{
          text-align: right;
          position:absolute;
        }
        .izq{
          float:left;
        }
        .der{
          float:right;
        }
</style>
</head>
<body> 
<table id="t01">
  <thead>
    <tr>
      <th COLSPAN=13>   
      <center>     
        <span class="ReporteAsistencia"><?php echo $titulo ?>
          </span>  
      </center>             
      </th>
    </tr>
    <tr >
      <th COLSPAN=3><span class="fecha"><?php echo $fechaactual;?></span>  </th>
      <th   COLSPAN=1> </th>
      <th   COLSPAN=1> </th>
      <th   COLSPAN=1> </th>
      <th   COLSPAN=1> </th>
      <th   COLSPAN=1> </th>
      <th>Semana:<?php echo $NoSemana;?></th>
      <th COLSPAN=2>del <?php echo $data[0]['FechaInicio'];?> </th>
      <th COLSPAN=2>a <?php echo $data[0]['FechaFin'];?></th>
    </tr>    
    <tr>
      <th>No</th>
      <th COLSPAN=8>Nombre</th>
      <th>Asistencias</th>
      <th>Retardos</th>
      <th>Faltas</th>
      <th>Tiempo Extra</th>
    </tr>
  </thead>
  <tbody>
  <?php
  
function toHours($min,$hr)
  { //obtener segundos
  $secMin = $min * 60;
    //obtener segundos 
  $secHr = $hr * 3600;
  $sec=$secMin+$secHr;
  //dias es la division de n segs entre 86400 segundos que representa un dia
  $dias=floor($sec/86400);
  //mod_hora es el sobrante, en horas, de la division de días; 
  $mod_hora=$sec%86400;
  //hora es la division entre el sobrante de horas y 3600 segundos que representa una hora;
  $horas=floor($mod_hora/3600); 
  //mod_minuto es el sobrante, en minutos, de la division de horas; 
  $mod_minuto=$mod_hora%3600;
  //minuto es la division entre el sobrante y 60 segundos que representa un minuto;
  $minutos=floor($mod_minuto/60);
    $horas=substr(str_repeat(0, 2).$horas, - 2);
    $minutos=substr(str_repeat(0, 2).$minutos, - 2);
    if($dias<=0){
      $text = $horas.':'.$minutos.':00';
    }else{
      $text = $dias.' '.$horas.':'.$minutos.':00';
    }
  return $text; 
  }


  $TotalFaltas=0;
  $TotalRetardos=0;
 // $TotalTiempoExtra= new DateTime('00:00:00');
  $TotalTiempoExtra= 0;
  $TotalEmpleados=0;
  $contador=0;
  $contador2=0;
  $OTRODEPTO=0;
  $depID=0;
  $count=0;
  $renglon=0;
  //print_r($data);
   foreach ($data as $row) {
     
    $contador=$contador+1; 
    $count=$count+1; 
    if($contador==1){
      
      $renglon= $renglon+1; 
      $depID=$row['nombreDepartamento'];
      echo '<tr>
            <td ><b>'.$row['departamento_id'].'</b></td>
            <td COLSPAN=10><b>'.$row['nombreDepartamento'].'</b></td>
          </tr>';
    }
      if($depID==$row['nombreDepartamento']){

      }else{
        
            $OTRODEPTO=1;
        $renglon= $renglon+1;
        
        //echo $dt;
        //die();
      $sumaTiempoExtra=toHours($sumaminutosTiempoExtra,$sumahorasTiempoExtra);
        echo  '<tr>
        <td class="subtotal"></td>
        <td COLSPAN=6 class="subtotal">SubTotal:</td>
        <td class="subtotal">'.$contador2.'</td>
        <td class="subtotal">Empleado(s)</td>
        <td class="subtotal">'.$sumaAsistencia.'</td>
        <td class="subtotal">'.$sumaRetardos.'</td>
        <td class="subtotal">'.$sumaFaltas.'</td>
        <td class="subtotal">'.$sumaTiempoExtra.'</td>
        </tr>';
      
            $TotalRetardos=$sumaRetardos+$TotalRetardos;
            $TotalFaltas= $sumaFaltas+$TotalFaltas;
            
            $horas = intval($row['TotalhorasExtra']); 
            $minutos= intval($row['TotalminutosExtra']); //nos saltamos los : puntos 
    
            $sumahorasTiempoExtra=$horas +$sumahorasTiempoExtra;
            $sumaminutosTiempoExtra=$minutos +$sumaminutosTiempoExtra;
            
            $sumaTiempoExtra=toHours($sumaminutosTiempoExtra,$sumahorasTiempoExtra);

            $TotalhorasTiempoExtra=$sumahorasTiempoExtra +$TotalhorasTiempoExtra;
              $TotalminutosTiempoExtra=$sumaminutosTiempoExtra +$TotalminutosTiempoExtra;


              $TotalTiempoExtra=toHours($TotalminutosTiempoExtra,$TotalhorasTiempoExtra);
              

              $totalAsistencia=$sumaAsistencia+$totalAsistencia;
          
            $TotalEmpleados=$TotalEmpleados+$contador2;            
            $sumaRetardos=0;
            $sumaFaltas=0;
            $sumahorasTiempoExtra=0;
            $sumaminutosTiempoExtra=0;
            $sumasegundosTiempoExtra=0;
            $sumaTiempoExtra='00:00:00';
            $sumaAsistencia=0;
            $contador2=0;  
            
        $renglon= $renglon+1;
        echo '<tr>
              <td ><b>'.$row['departamento_id'].'</b></td>
              <td COLSPAN=10><b>'.$row['nombreDepartamento'].'</b></td>
            </tr>';
      }
      $contador2=$contador2+1;
      if (strlen($row['LunesEntrada'])>3){
          $row['LunesEntrada']=trim(substr($row['LunesEntrada'], 0, -3));
          $row['LunesSalida']=trim(substr($row['LunesSalida'], 0, -3));        
      } 
      if(strlen($row['LunesSalida'])>3  ||  strlen($row['LunesEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }     
      if (strlen($row['MartesEntrada'])>3){
        $row['MartesEntrada']=trim(substr($row['MartesEntrada'], 0, -3));
        $row['MartesSalida']=trim(substr($row['MartesSalida'], 0, -3));     
      }
      if(strlen($row['MartesSalida'])>3  ||  strlen($row['MartesEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }
      if (strlen($row['MiercolesEntrada'])>3){
        $row['MiercolesEntrada']=trim(substr($row['MiercolesEntrada'], 0, -3));
        $row['MiercolesSalida']=trim(substr($row['MiercolesSalida'], 0, -3));     
      }
      if(strlen($row['MiercolesSalida'])>3  ||  strlen($row['MiercolesEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }
      if (strlen($row['JuevesEntrada'])>3){
        $row['JuevesEntrada']=trim(substr($row['JuevesEntrada'], 0, -3));
        $row['JuevesSalida']=trim(substr($row['JuevesSalida'], 0, -3));     
      }
      if(strlen($row['JuevesSalida'])>3  ||  strlen($row['JuevesEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }
      if (strlen($row['ViernesEntrada'])>3){
        $row['ViernesEntrada']=trim(substr($row['ViernesEntrada'], 0, -3));
        $row['ViernesSalida']=trim(substr($row['ViernesSalida'], 0, -3));     
      }
      if(strlen($row['ViernesSalida'])>3  ||  strlen($row['ViernesEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }
      if (strlen($row['SabadoEntrada'])>3){
        $row['SabadoEntrada']=trim(substr($row['SabadoEntrada'], 0, -3));
        $row['SabadoSalida']=trim(substr($row['SabadoSalida'], 0, -3));     
      }
      if(strlen($row['SabadoSalida'])>3  ||  strlen($row['SabadoEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }
      if (strlen($row['DomingoEntrada'])>3){
        $row['DomingoEntrada']=trim(substr($row['DomingoEntrada'], 0, -3));
        $row['DomingoSalida']=trim(substr($row['DomingoSalida'], 0, -3));     
      }
      if(strlen($row['DomingoSalida'])>3  ||  strlen($row['DomingoEntrada'])>3){
        $AsistenciaSemanal=$AsistenciaSemanal+1;
      }
          $renglon= $renglon+1;
          echo '<tr>
            <td>'.$row['Numero'].'</td>
            <td COLSPAN=8>'.$row['Nombre'].'</td>
            <td class="alinearDerecha">'.$AsistenciaSemanal.'</td>
            <td class="alinearDerecha">'.$row['RetardoSemanal'].'</td>
            <td class="alinearDerecha">'.$row['FaltasTotales'].'</td>
            <td class="alinearDerecha">'.$row['TotalTiempoExtra'].'</td>
          </tr>';
        $sumaRetardos=$row['RetardoSemanal']+$sumaRetardos;        
        $sumaFaltas=$row['FaltasTotales']+$sumaFaltas;
        

        $sumaAsistencia=$AsistenciaSemanal+$sumaAsistencia;

        $horas = intval($row['TotalhorasExtra']); 
        $minutos= intval($row['TotalminutosExtra']); //nos saltamos los : puntos 

        $sumahorasTiempoExtra=$horas +$sumahorasTiempoExtra;
        $sumaminutosTiempoExtra=$minutos +$sumaminutosTiempoExtra;

        $sumaTiempoExtra=toHours($sumaminutosTiempoExtra,$sumahorasTiempoExtra);

        $AsistenciaSemanal=0;
        if( $OTRODEPTO==0){
          if(count($data)==$contador){
          
            $renglon= $renglon+1;
              echo  '<tr>
              <td class="subtotal"></td>
              <td COLSPAN=6 class="subtotal">SubTotal:</td>
              <td class="subtotal">'.$contador2.'</td>
              <td class="subtotal">Empleado(s)</td>
              <td class="subtotal">'.$sumaAsistencia.'</td>
              <td class="subtotal">'.$sumaRetardos.'</td>
              <td class="subtotal">'.$sumaFaltas.'</td>
              <td class="subtotal">'.$sumaTiempoExtra.'</td>
              </tr>';
        
              $TotalRetardos=$sumaRetardos+$TotalRetardos;
              $TotalFaltas= $sumaFaltas+$TotalFaltas;
            
              $TotalhorasTiempoExtra=$sumahorasTiempoExtra +$TotalhorasTiempoExtra;
              $TotalminutosTiempoExtra=$sumaminutosTiempoExtra +$TotalminutosTiempoExtra;
              $TotalTiempoExtra=toHours($TotalminutosTiempoExtra,$TotalhorasTiempoExtra);

              $totalAsistencia=$sumaAsistencia+$totalAsistencia;

              $TotalEmpleados=$TotalEmpleados+$contador2;            
              $sumaRetardos=0;
              $sumaFaltas=0;
              $contador2=0;  
              $sumahorasTiempoExtra=0;
              $sumaminutosTiempoExtra=0;
              $sumasegundosTiempoExtra=0;
              $sumaAsistencia=0;
              $sumaTiempoExtra='00:00:00';
        }
      }
      else {

        
        //$sumaTiempoExtra=$dt;
        $depID=$row['nombreDepartamento'];
        
      }

      /*aquiiiii!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!*/ 
      
    if(count($data)==$count){
      
      $renglon= $renglon+1;
      
      
        if($contador2>0){

          $sumaTiempoExtra=toHours($sumaminutosTiempoExtra,$sumahorasTiempoExtra);
          echo  '<tr>
          <td class="subtotal"></td>
          <td COLSPAN=6 class="subtotal">SubTotal:</td>
          <td class="subtotal">'.$contador2.'</td>
          <td class="subtotal">Empleado(s)</td>
          <td class="subtotal">'.$sumaAsistencia.'</td>
          <td class="subtotal">'.$sumaRetardos.'</td>
          <td class="subtotal">'.$sumaFaltas.'</td>
          <td class="subtotal">'.$sumaTiempoExtra.'</td>
          </tr>';

          $TotalhorasTiempoExtra=$sumahorasTiempoExtra +$TotalhorasTiempoExtra;
          $TotalminutosTiempoExtra=$sumaminutosTiempoExtra +$TotalminutosTiempoExtra;
          
          $TotalTiempoExtra=toHours($TotalminutosTiempoExtra,$TotalhorasTiempoExtra);
          

          $totalAsistencia=$sumaAsistencia+$totalAsistencia;
        }
    
          $TotalRetardos=$sumaRetardos+$TotalRetardos;
          $TotalFaltas= $sumaFaltas+$TotalFaltas;
          

          $TotalEmpleados=$TotalEmpleados+$contador2;            
          $sumaRetardos=0;
          $sumaFaltas=0;
          $contador2=0;  
    }
    if( $renglon>=68){
      /*if($contador==19){*/
        echo '<!---51 rengones salto de linea-->
        <tr>
          <td COLSPAN=13><hr><hr></td>
        </tr>';
        $renglon=0;
      }else{
      }
  }
  ?>
      <tr>
        <td class="total"></td>
        <td COLSPAN=2 class="total">Total:</td>
        <td class="total"><?php echo $TotalEmpleados ?></td>
        <td class="total">Empleado(s)</td>
        <td class="total"></td>
        <td class="total"></td>
        <td class="total"></td>
        <td class="total"></td>
        <td class="total"><?php echo $totalAsistencia ?></td>
        <td class="total"><?php echo $TotalRetardos ?></td>
        <td class="total"><?php echo $TotalFaltas ?></td>
        <td class="total"><?php echo $TotalTiempoExtra //echo $TotalTiempoExtra ?></td>
      </tr>

  </tbody>
  <tfoot>      
  </tfoot>
</table>
</body>
</html>
