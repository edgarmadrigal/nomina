<html>
    <head>
        <title>Contrato</title>
        <meta charset="UTF-8">
        <style>
            body{
                font-size: 11px!important;
            }
        </style>
    </head>
    <body>  
    <p align="center" style="font-family: Stencil;">   
    <b> <?php 
    try{
            if($SALARIO >73.04){
                echo $RAZONSOCIAL;               
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?> </b>
    <br>
    <b>S. DE R. L. DE C. V. </b>
    <HR ></HR>
    </p>
        <p align="center">
            <strong>LA EMPRESA </strong>
            <strong><?php if($SALARIO >73.04){ 
        
                echo $RAZONSOCIAL;  
              
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?></strong>
            <strong> S DE R L DE C V</strong>
        </p>
            Contrato individual de trabajo por tiempo indeterminado que celebran por
            una parte "LA EMPRESA" <?php if($SALARIO >73.04){ 
        
                echo $RAZONSOCIAL;  
              
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?>, S. DE R.L. DE C.V
        <br>
            Representada por el Lic. <strong><?php echo $REPRESENTANTE; ?></strong> y por la otra parte,
            "EL TRABAJADOR" <strong><?php echo $NOMBRE; ?></strong>
        <br>
            al tenor de las siguientes Declaraciones y Cláusulas.
        </p>
        <p align="center">
            <strong> ANTECEDENTES</strong>
        </p>
        <p>
            1.-"LA EMPRESA" denominada <?php if($SALARIO >73.04){ 
        
                echo $RAZONSOCIAL;  
              
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?>, S. DE RL. DE C.V es
            una sociedad legalmente constituida según las leyes mexicanas ante la fe
            <?php echo $NOTARIO; ?>
            y legalmente
            representada por su Apoderado Legal el Lic.  <strong><?php echo $REPRESENTANTE; ?></strong>.
        </p>
        <p>
            2.- "EL TRABAJADOR" <strong><?php echo $NOMBRE; ?></strong> manifiesta bajo protesta decir verdad de ser
            nacionalidad MEXICANA de años de edad, estado civil <strong><?php echo $EDOCIVIL; ?></strong> con domicilio
            en <strong><?php print_r($DIRECCION); ?></strong>
        </p>
        <p>
            Registro de afiliación al IMSS <strong><?php echo $IMSS; ?></strong> , RFC <strong><?php echo $RFC; ?></strong> CURP <strong><?php echo $CURP; ?></strong>
        </p>
        <p>
            3.- "LA EMPRESA" <?php if($SALARIO >73.04){ 
        
                echo $RAZONSOCIAL;  
              
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?>, S. DE R.L.DE C.V tiene como
            objeto social La PRESTACION DE SERVICIOS PROFESIONALES Y TECNICOS
        </p>
        <p>
            4.- "EL TRABAJADOR" <strong><?php echo $NOMBRE; ?></strong> manifiesta que tiene la capacidad y aptitudes de
            desarrollar el trabajo para el cual ha sido contratado.
        </p>
        <p>
            5.- "LA EMPRESA" <?php if($SALARIO >73.04){ 
        
                echo $RAZONSOCIAL;  
              
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?>, S. DE R.L DE .C.V requiere de
            los servicios de personal apto para el desarrollo de sus Actividades y de
            modo especial para el puesto o funciones de <strong><?php echo $PUESTO; ?></strong> 
        </p>
        <p>
            6.- "EL TRABAJADOR" <strong><?php echo $NOMBRE; ?></strong> esta conforme en desempeñar los requerimientos de la
            empresa y en plasmar las condiciones generales de trabajo sobre los cuales
            prestara sus servicios personales.
        </p>
        <p align="center">
            <strong> CLAUSULAS</strong>
        </p>
        <p>
            PRIMERA.- Para efectos de mayor brevedad se denominara en lo sucesivo a 
            <?php if($SALARIO >73.04){ 
        
                echo $RAZONSOCIAL;  
              
            }else{
                echo "MANUFACTURAS LAJAT"; 

            } 
             ?>, S. DE R.L DE C.V como "LA EMPRESA", a  <strong><?php echo $NOMBRE; ?></strong>     
            
            como "EL TRABAJADOR", a la Ley Federal del Trabajo como "LA LEY", al
            referirse al presente documento como "EL CONTRATO", y a los que lo
            suscriben como "LAS PARTES".
        </p>
        <p>
            SEGUNDA.- "EL CONTRATO", se celebrara por tiempo indeterminado según lo
            establece el articulo 35 de "LA LEY".
        </p>
        <p>
            TERCERA.- La prestación de los servicios del "EL TRABAJADOR" consistiría
            en: <strong><?php echo $PUESTO; ?></strong>  -Las cuales desarrollara de acuerdo a la definición del puesto
            obligándose "EL TRABAJADOR" a atender cualquier otra actividad conexa a su
            ocupación
        </p>
        <p>
            principal y que "LA EMPRESA"le señale.
        </p>
        <p>
            CUARTA.- El lugar de la prestación de los servicios de "EL TRABAJADOR" será el domicilio "LA EMPRESA" ubicado en :
             <strong><?php echo $DIRECCIONEMPRESA; ?></strong>
        </p>
        <p>
            Asimismo "LAS PARTES" convienen y acepta "EL TRABAJADOR" que cuando por
            razones administrativas o de desarrollo de la actividad o prestación de
            servicios contratados haya necesidad de removerlo, podrá trasladarse al
            lugar que "LA EMPRESA" le asigne siempre y cuando no se vea menos acabado
            su salario.
        </p>
        <p>
            En este caso "LA EMPRESA", le comunicara con anticipación la remoción del
            lugar de prestación de servicios indicándoles el nuevo lugar asignado
        </p>
        <p>
        Para el caso de que el nuevo lugar de prestación de servicios que le fuera asignado variara el horario de labores “ EL TRABAJADOR “ acepta allanarse a dicha 
        modalidad y en los términos del articulo 59 de “LA LEY “ para que “EL TRABAJADOR “ disfrute de un descanso mayor al comúnmente establecido, se podrá 
        ampliar el horario de labores.
        </p>
        <p>
        QUINTA .- La duración de la jornada de trabajo será de 48 horas, quedando distribuido a manera enunciativa como sigue: 
        </p>
        <p>
        <strong><?php echo $HORARIOCONTRATO; ?></strong>
        </p>
        <p>
        Cuando el horario de labores sea continuo "EL TRABAJADOR", tendrá derecho a <strong><?php echo $COMIDA; ?></strong> de descanso para tomar alimentos y le será  computado dicho
        periodo dentro de su jornada de trabajo.
        </p><p>
        En el caso de cambio o remoción por necesidades de la empresa del lugar de prestación de servicios "EL TRABAJADOR" acepta que el horario podrá variar y se 
        allana a dicha modalidad, siempre y cuando el horario nunca sea mayor al legal ordinario.
        "EL TRABAJADOR" , únicamente podrá laborar tiempo extraordinario cuando "LA EMPRESA", se lo indique y medie orden  por escrito, la que señalara el día o los 
        días y el horario en el cual se desempeñara la misma. Para el caso de computar el  tiempo extraordinario laborado deberá "EL TRABAJADOR", recabar y
        conservar la orden referida a fin de que en su momento quede debidamente pagado el tiempo extra laborado, la falta de presentación de esa orden solo es 
        imputable a "EL TRABAJADOR". Las partes manifiestan que salvo esta forma queda prohibido en el centro de trabajo laborar horas extras.
        </p>
        <p>
        SEXTA.- "EL TRABAJADOR", acepta y por ende queda por establecido que cuando por razones convenientes para "LA EMPRESA", esta modifique el horario de
        trabajo, podrá desempeñar su jornada en el que quede establecido.
        </p>
        <p><br>
        SEPTIMA.- "EL TRABAJADOR", deberá presentarse puntualmente a sus labores en el horario de trabajo establecido y  cumplir con los sistemas de registro y
        control de asistencia diaria que la empresa establezca. En caso de retraso o falta de asistencia injustificada podrá "LA EMPRESA", imponerle la corrección
        disciplinaria que contempla el Reglamento Interior de Trabajo, o "LA LEY".
        </p>
        <p>
        OCTAVA.- "EL TRABAJADOR", percibirá por la prestación de sus servicios como salario diario la cantidad de <strong><?php echo $SALARIO;?></strong> <strong><?php echo $DESCRIPCIONSALARIO;?></strong> los cuales 
        serán cubiertos en efectivo y en Moneda Nacional del Año corriente. 
        Del salario anterior "LA EMPRESA" hará por cuenta de "EL TRABAJADOR", las deducciones legales correspondientes, particularmente las que se refiere a 
        impuestos sobre la renta, seguro social, cuotas sindicales, etcétera, así como aquellas autorizadas expresamente por el trabajador o determinadas por la 
        autoridad competente de acuerdo a lo establecido por el  art. 110 de la ley. "EL TRABAJADOR", deberá cada vez que le sea pagado su salario extender a favor de 
        la "EMPRESA", el  recibo correspondiente en los documentos que la misma le presente para tales fines. 
        </p>
        <p>
        NOVENA.- "EL TRABAJADOR", recibirá el pago de su salario por transferia bancaria de las prestaciónes de sus servicios. 
        "LA EMPRESA" pagara su salario a "EL TRABAJADOR", los días VIERNES de cada semana cuando "EL TRABAJADOR", contratado sea semanal, cada veinticinco 
        días, precisamente los días 25 de cada mes cuando corresponda a "EL TRABAJADOR" de oficina.         
        </p>
        <p> 
        DECIMA.- "EL TRABAJADOR" tendrá derecho por cada cinco días de labores a disfrutar de un día de descanso con el pago  de salario diario correspondiente. 
        Queda establecido preferentemente como día de descanso semanal el día <strong><?php echo $DESCANSO;?></strong> de cada semana,pudiendo ser cambiado el mismo por las necesidades 
        de la empresa.    
       </p>
        <p>
        DECIMA PRIMERA.- Cuando "EL TRABAJADOR", por razones administrativas tenga que laborar el día domingo "LA EMPRESA", le pagara, además de su salario 
        ordinario, el 25% (veinticinco por ciento) como prima dominical sobre el salario devengado, siempre y cuando su día de descanso no sea en domingo.
        </p>
        <p>
        DECIMA SEGUNDA.- Quedan establecidos como días de descanso obligatorio los señalados en el articulo 74 "LA LEY".
        </p>
        <p>
        DECIMA TERCERA.- "EL TRABAJADOR", tendrá derecho a disfrutar de un periodo anual de vacaciones según lo Establecido en el articulo 76 de 
        "LA LEY", tomando en consideración la antigüedad, así como a disfrutar el salario que le  corresponda. De igual modo percibirá la Prima Vacacional respectiva 
        equivalente al 25% del importe pagado por  vacaciones.
        </p>
        <p>
        DECIMA CUARTA.- "EL TRABAJADOR", tendrá derecho a recibir por parte de "LA EMPRESA", antes del día 20 de  diciembre de cada año el importe 
        correspondiente a quince días de salario como pago del aguinaldo a que se refiere el articulo 87 de "LA LEY, o su parte proporcional por fracción del año.
        </p>
        <p>
        DECIMA QUINTA.- "EL TRABAJADOR", acepta someterse a los exámenes médicos que periódicamente establezca  "LA EMPRESA", en los términos del artículo 
        134 fracción X de "LA LEY", a fin de mantener en forma óptima sus facultades físicas e intelectuales, para el mejor desempeño de sus funciones.  
        </p>
        <p>
        DECIMA SEXTA.- "EL TRABAJADOR", deberá integrarse a los Planes, programas y Comisiones Mixtas de Capacitación y adiestramiento así como a los 
        de Seguridad e Higiene en el Trabajo que tienen constituidos "LA EMPRESA",  tomando  parte activa dentro de los mismos según los cursos establecidos y medidas 
        preventivas de trabajo.
        </p>
        <p>
        DECIMA SEPTIMA.- "EL TRABAJADOR", esta obligado a observar y cumplir las disposiciones del reglamento Interior de Trabajo con que cuenta "LA EMPRESA" y 
        que tiene fijado en las áreas de mayor visibilidad.            
        </p>
        <p>
        DECIMA OCTAVA.- "EL TRABAJADOR", deberá dar fiel cumplimiento a las disposiciones contenidas en al articulo 134 de "LA LEY", y que corresponden a las 
        obligaciones de los trabajadores en el desempeño de sus labores al servicio de "LA EMPRESA".
        </p>
        <p>
        DECIMA NOVENA.- Para todo lo no previsto en el presente "CONTRATO", se estará a lo contenido en el contrato colectivo de trabajo, en "LA LEY", así como el 
        Reglamento Interior de Trabajo.
        </p>
        <p>
        VIGESIMA.- "LAS PARTES" reconocen como fecha de antigüedad o de inicio de prestación de servicios del "TRABAJADOR",
        a partir del día <strong><?php echo $FECHAANTIGUEDAD; ?></strong> 
        </p>
        <p>
        VIGESIMA PRIMERA.- EL TRABAJADOR DESIGNA A : <strong><?php echo $BENEFICIARIO; ?></strong> COMO BENEFICIARIO COMO LO REFIERE EL ARTICULO 501 DE LA LFT ,
        PARA EL PAGO DE LOS SALARIOS Y PRESTACIONES DEVENGADAS Y NO COBRADAS A LA MUERTE DE LOS TRABAJADORES O LAS QUE SE GENEREN POR SU FALLECIMIENTO O 
        DESAPARICION DERIVADA DE UN ACTO DELINCUENCIAL.  
        </p>
        LEIDO  QUE  FUE  EL  PRESENTE  CONTRATO  POR  QUIENES  EN  EL  INTERVIENEN  LO  RATIFICAN E  IMPUESTOS  DE  SU  CONTENIDO  LO SUSCRIBEN  
        POR  DUPLICADO  QUEDANDO  EL  ORIGINAL  EN  EL  PODER  DE  LA  EMPRESA  Y  LA COPIA  PARA  EL  TRABAJADOR  EN  LA  CIUDAD  DE  <?php echo $EMPRESA; ?>        
        <strong><?php ECHO $FECHAIMPRESION;
        } 
         catch (Exception $e) {
          echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
        ?></strong> 
        </p>
        <strong>
            <TABLE>
                <TR>
                    <TD width="200px">&nbsp;</TD><TD>&nbsp;</TD><TD width="50px">&nbsp;&nbsp;&nbsp;
                <BR><BR></TD><TD>&nbsp;</TD><TD></TD>
                </TR>
                <TR>
                    <TD width="200px">&nbsp;</TD><TD>________________________</TD><TD width="50px">&nbsp;&nbsp;&nbsp;</TD><TD>________________________</TD><TD></TD>
                </TR>
                <TR>
                    <TD  width="200px">&nbsp;</TD><TD><center>LA EMPRESA</center></TD><TD width="50px">&nbsp;&nbsp;&nbsp;</TD><TD><center>EL TRABAJADOR</center></TD><TD></TD>
                </TR>
                <TR>
                    <TD width="200px">&nbsp;</TD><TD>&nbsp;</TD><TD width="50px">&nbsp;&nbsp;&nbsp;
                <BR><BR><BR><BR><BR><BR><BR></TD><TD>&nbsp;</TD><TD></TD>
                </TR>
                <TR>
                    <TD width="200px">&nbsp;</TD><TD>________________________</TD><TD width="50px">&nbsp;&nbsp;&nbsp;</TD><TD>________________________</TD><TD></TD>
                </TR>
                <TR>
                    <TD  width="200px">&nbsp;</TD><TD><center>TESTIGO</center></TD><TD width="50px">&nbsp;&nbsp;&nbsp;</TD><TD><center>TESTIGO</center></TD><TD></TD>
                </TR>
            </TABLE>
            </strong> 
        <p></p>
    </body>
</html>