<html>
<head>
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
      <th> </th>
      <th></th>
      <th> </th>
      <th> </th>
      <th> </th>
      <th>Semana:<?php echo $NoSemana;?></th>
      <th COLSPAN=2>del <?php echo $data[0]['FechaInicio'];?> </th>
      <th COLSPAN=2>a <?php echo $data[0]['FechaFin'];?></th>
    </tr>    
    <tr>
      <th>No</th>
      <th COLSPAN=2>Nombre_______________________________</th>
      <th>Lunes_____</th>
      <th>Martes____</th>
      <th>Miercoles_</th>
      <th>Jueves____</th>
      <th>Viernes___</th>
      <th>Sabado____</th>
      <th>Domingo___</th>
      <th>Ret</th>
      <th>Falta</th>
      <th>Extra_____</th>
    </tr>
  </thead>
  <tbody>
  <?php
  

  $TotalFaltas=0;
  $TotalRetardos=0;
  $TotalTiempoExtra= new DateTime('00:00:00');
  $TotalEmpleados=0;
  $contador=0;
  $contador2=0;
  $depID=0;
   foreach ($data as $row) {
    $contador=$contador+1; 
    if($contador==1){
      $depID=$row['departamento_id'];
    }
    if($contador==19){
      echo '<!---51 rengones salto de linea-->
      <tr>
        <td COLSPAN=13><hr><hr></td>
      </tr>';
      $contador=0;
    }else{
      if($depID==$row['departamento_id']){

      }else{
      echo '<tr>
            <td ><b>'.$row['departamento_id'].'</b></td>
            <td COLSPAN=2><b>'.$row['nombreDepartamento'].'</b></td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td> </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
          </tr>';
      }
      $contador2=$contador2+1;
          echo '<tr>
            <td>'.$row['Numero'].'</td>
            <td COLSPAN=2>'.$row['Nombre'].'</td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['LunesEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['LunesSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['MartesEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['MartesSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['MiercolesEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['MiercolesSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['JuevesEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['JuevesSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['ViernesEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['ViernesSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['SabadoEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['SabadoSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha"><span class="izq">'.substr($row['DomingoEntrada'], 0, -3).'</span> <span class="der"> '.substr($row['DomingoSalida'], 0, -4).'</span> </td>
            <td class="alinearDerecha">'.$row['RetardoSemanal'].'</td>
            <td class="alinearDerecha">'.$row['FaltasTotales'].'</td>
            <td class="alinearDerecha">'.$row['TotalTiempoExtra'].'</td>
          </tr>';
        $sumaRetardos=$row['RetardoSemanal']+$sumaRetardos;        
        $sumaFaltas=$row['FaltasTotales']+$sumaFaltas;
        

        $horas = substr($row['TotalTiempoExtra'],0,2); 
        $minutos = substr($row['TotalTiempoExtra'],3,2); //nos saltamos los : puntos 
        $segundos = substr($row['TotalTiempoExtra'],1,2); //nos saltamos los : puntos  

        $iv = new DateInterval("PT{$horas}H{$minutos}M");
        $dt->add($iv);

      if( $depID==$row['departamento_id']  ){
      }else {
        $sumaTiempoExtra=$dt->format('H:i:s');
        $depID=$row['departamento_id'];
      echo  '<tr>
              <td class="subtotal"></td>
              <td COLSPAN=2 class="subtotal">SubTotal:</td>
              <td class="subtotal">'.$contador2.'</td>
              <td class="subtotal">Empleado(s)</td>
              <td class="subtotal"></td>
              <td class="subtotal"></td>
              <td class="subtotal"></td>
              <td class="subtotal"></td>
              <td class="subtotal"></td>
              <td class="subtotal">'.$sumaRetardos.'</td>
              <td class="subtotal">'.$sumaFaltas.'</td>
              <td class="subtotal">'.$sumaTiempoExtra.'</td>
            </tr>';
            $TotalRetardos=$sumaRetardos+$TotalRetardos;
            $TotalFaltas= $sumaFaltas+$TotalFaltas;
          
            $horas = substr($sumaTiempoExtra,0,2); 
            $minutos = substr($sumaTiempoExtra,3,2); //nos saltamos los : puntos 
            $interval = new DateInterval("PT{$horas}H{$minutos}M");
            $TotalTiempoExtra->add($interval);
            $TotalEmpleados=$TotalEmpleados+$contador2;            
            $sumaRetardos=0;
            $sumaFaltas=0;
            $sumaTiempoExtra= new DateTime('00:00:00');
            $contador2=0;  
            $dt=new DateTime('00:00:00');
      }
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
        <td class="total"></td>
        <td class="total"><?php echo $TotalRetardos ?></td>
        <td class="total"><?php echo $TotalFaltas ?></td>
        <td class="total"><?php echo $TotalTiempoExtra->format('H:i:s') ?></td>
      </tr>

  </tbody>
  <tfoot>      
  </tfoot>
</table>
</body>
</html>
