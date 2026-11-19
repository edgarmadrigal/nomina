<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gráficas de Inicio. Usa dbo.asistenciaBioStar: un renglón por empleado y
 * semana que llena el botón "Procesar" de Checadas (asistencias, faltas y
 * retardos por día), y dbo.incidencias para separar vacaciones e incidencias.
 */
class DashboardModel extends CI_Model {

    // Días de la semana tal como se llaman las columnas de asistenciaBioStar
    private $dias = array(1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves',
                          5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo');

    function __construct(){
        parent::__construct();
    }

    /**
     * Desdobla los 7 días de cada renglón y clasifica cada día (columna k.clase):
     *   FUTURO      día que todavía no llega (semana en curso): no cuenta
     *   VACACIONES  incidencia de vacaciones en un día laboral
     *   INCIDENCIA  otra incidencia (incapacidad, permiso, defunción...) en un día laboral
     *   FALTA / ASISTENCIA  lo que dejó el botón Procesar
     *   NADA        descanso
     * Procesar marca las vacaciones como asistencia (para que no sean falta), por eso
     * aquí se separan con dbo.incidencias. Día laboral = tiene asistencia o falta.
     */
    private function desdoblar(){
        $v = array();
        foreach ($this->dias as $n => $d) {
            $v[] = "($n, a.Asistencia$d, a.Falta$d, a.Retardo$d, a.{$d}Entrada, a.{$d}Salida)";
        }
        return "CROSS APPLY (VALUES " . implode(",
", $v) . ") d (dia, asistencia, falta, retardo, entrada, salida)
             CROSS APPLY (SELECT CAST(DATEADD(DAY, d.dia - 1, a.FechaInicio) AS DATE) AS fecha) f
             OUTER APPLY (SELECT TOP 1 c.descripcion AS concepto,
                                 CASE WHEN c.descripcionCorta = 'VAC' AND c.descripcion LIKE 'VACACION%' THEN 1 ELSE 0 END AS esVacacion
                          FROM dbo.incidencias i
                          JOIN dbo.conceptos c ON c.id = i.concepto_id
                          WHERE i.empleado_id = TRY_CAST(a.Numero AS INT) AND i.estatus = 1
                            AND (i.empresa_id = a.empresa_id OR i.empresa_id IS NULL)
                            AND f.fecha BETWEEN i.fechaInicio AND i.fechaFin
                            AND c.descripcionCorta <> 'tran'
                          ORDER BY CASE WHEN c.descripcion LIKE 'VACACION%' THEN 0 ELSE 1 END) inc
             CROSS APPLY (SELECT CASE
                     WHEN f.fecha > CAST(GETDATE() AS DATE) THEN 'FUTURO'
                     WHEN inc.concepto IS NOT NULL AND (d.asistencia > 0 OR d.falta > 0)
                          THEN CASE WHEN inc.esVacacion = 1 THEN 'VACACIONES' ELSE 'INCIDENCIA' END
                     WHEN d.falta > 0 THEN 'FALTA'
                     WHEN d.asistencia > 0 THEN 'ASISTENCIA'
                     ELSE 'NADA' END AS clase) k";
    }

    /** Conteo por clase (retardos = asistencias con retardo). */
    private function conteos(){
        return "SUM(CASE WHEN k.clase = 'ASISTENCIA' THEN 1 ELSE 0 END) AS asistencias,
                SUM(CASE WHEN k.clase = 'FALTA' THEN 1 ELSE 0 END) AS faltas,
                SUM(CASE WHEN k.clase = 'ASISTENCIA' AND d.retardo > 0 THEN 1 ELSE 0 END) AS retardos,
                SUM(CASE WHEN k.clase = 'VACACIONES' THEN 1 ELSE 0 END) AS vacaciones,
                SUM(CASE WHEN k.clase = 'INCIDENCIA' THEN 1 ELSE 0 END) AS incidencias";
    }

    /** Filtro de plantas: una planta, o las empresas permitidas del usuario (vacío = todas). */
    private function filtroPlanta($planta, array $empresas, array &$params){
        if ($planta !== '' && $planta !== null) {
            $params[] = (int) $planta;
            return " AND a.empresa_id = ?";
        }
        if (!empty($empresas)) {
            return " AND a.empresa_id IN (" . implode(',', array_map('intval', $empresas)) . ")";
        }
        return "";
    }

    /** Semanas procesadas (las más recientes primero). */
    public function semanas(array $empresas){
        $params = array();
        $f = $this->filtroPlanta('', $empresas, $params);
        $sql = "SELECT TOP 30 a.anio, a.NoSemana, MIN(a.FechaInicio) AS inicio, MAX(a.FechaFin) AS fin
                FROM dbo.asistenciaBioStar a WHERE 1 = 1 $f
                GROUP BY a.anio, a.NoSemana
                ORDER BY a.anio DESC, a.NoSemana DESC";
        return $this->db->query($sql, $params)->result_array();
    }

    /** Totales por día (asistencias, faltas, retardos) y faltas por planta. */
    public function resumen($anio, $semana, $planta, array $empresas){
        $params = array((int) $anio, (int) $semana);
        $f = $this->filtroPlanta($planta, $empresas, $params);
        $porDia = $this->db->query(
            "SELECT d.dia, " . $this->conteos() . "
             FROM dbo.asistenciaBioStar a
             " . $this->desdoblar() . "
             WHERE a.anio = ? AND a.NoSemana = ? $f
             GROUP BY d.dia ORDER BY d.dia", $params)->result_array();

        $params2 = array((int) $anio, (int) $semana);
        $f2 = $this->filtroPlanta($planta, $empresas, $params2);
        $porPlanta = $this->db->query(
            "SELECT a.empresa_id, MAX(a.nombreEmpresa) AS planta,
                    COUNT(DISTINCT a.Numero) AS empleados, " . $this->conteos() . "
             FROM dbo.asistenciaBioStar a
             " . $this->desdoblar() . "
             WHERE a.anio = ? AND a.NoSemana = ? $f2
             GROUP BY a.empresa_id ORDER BY faltas DESC", $params2)->result_array();

        return array('porDia' => $porDia, 'porPlanta' => $porPlanta);
    }

    /**
     * Empleados detrás de un punto de la gráfica.
     * $tipo: asistencias / faltas / retardos / vacaciones / incidencias.  $dia: 1..7 o 0 = toda la semana.
     */
    public function detalle($anio, $semana, $planta, $dia, $tipo, array $empresas){
        $columna = array('asistencias' => "k.clase = 'ASISTENCIA'", 'faltas' => "k.clase = 'FALTA'",
                         'retardos' => "k.clase = 'ASISTENCIA' AND d.retardo > 0",
                         'vacaciones' => "k.clase = 'VACACIONES'", 'incidencias' => "k.clase = 'INCIDENCIA'");
        if (!isset($columna[$tipo])) return array();
        $params = array((int) $anio, (int) $semana);
        $f = $this->filtroPlanta($planta, $empresas, $params);
        $fd = '';
        if ((int) $dia >= 1 && (int) $dia <= 7) {
            $fd = " AND d.dia = ?";
            $params[] = (int) $dia;
        }
        $sql = "SELECT a.Numero AS numero, a.Nombre AS nombre, a.nombreEmpresa AS planta,
                       a.nombreDepartamento AS departamento, a.nombrePuesto AS puesto,
                       d.dia, LTRIM(RTRIM(ISNULL(d.entrada, ''))) AS entrada, LTRIM(RTRIM(ISNULL(d.salida, ''))) AS salida,
                       ISNULL(inc.concepto, '') AS concepto
                FROM dbo.asistenciaBioStar a
                " . $this->desdoblar() . "
                WHERE a.anio = ? AND a.NoSemana = ? AND " . $columna[$tipo] . " $f $fd
                ORDER BY a.nombreEmpresa, a.Nombre, d.dia";
        $filas = $this->db->query($sql, $params)->result_array();
        foreach ($filas as &$r) $r['nombreDia'] = $this->dias[(int) $r['dia']];
        return $filas;
    }
}
