<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Catálogo de relojes checadores (dbo.RelojDispositivo).
 * Lo usa tools/RelojSync/RelojSync.exe para descargar las checadas.
 */
class RelojDispositivoModel extends CI_Model {

    function __construct(){
        parent::__construct();
    }

    /**
     * deviceId es BIGINT y las fechas se convierten a VARCHAR para que
     * lleguen como texto al JSON (el driver sqlsrv regresa DateTime).
     */
    public function listar(){
        $sql = "SELECT d.id,
                       CONVERT(VARCHAR(20), d.deviceId) AS deviceId,
                       d.descripcion, d.ip, d.puerto, d.sdk,
                       d.idPlanta, e.nombre AS planta,
                       d.grupoPlantas, d.zonaHoraria,
                       CONVERT(VARCHAR(10), d.fechaInicio, 23) AS fechaInicio,
                       CONVERT(VARCHAR(19), d.ultimaDescarga, 120) AS ultimaDescarga,
                       d.ultimoError, d.activo, d.ultimasChecadas,
                       (SELECT CONVERT(VARCHAR(19), MAX(r.fechahora), 120) FROM dbo.RelojEvento r
                         WHERE r.deviceId = d.deviceId AND r.esChecada = 1) AS ultimaChecada,
                       (SELECT COUNT(*) FROM dbo.RelojEvento r
                         WHERE r.deviceId = d.deviceId AND r.esChecada = 1
                           AND r.fechahora >= CONVERT(DATE, GETDATE())) AS checadasHoy
                FROM dbo.RelojDispositivo d
                LEFT JOIN dbo.empresas e ON e.id = d.idPlanta
                ORDER BY d.activo DESC, d.descripcion";
        return $this->db->query($sql)->result_array();
    }

    public function plantas(){
        $sql = "SELECT id, nombre FROM dbo.empresas WHERE estatus = 1 ORDER BY id";
        return $this->db->query($sql)->result_array();
    }

    public function existeDeviceId($deviceId, $excluirId = null){
        $sql = "SELECT COUNT(*) AS total FROM dbo.RelojDispositivo WHERE deviceId = ?";
        $params = array($deviceId);
        if ($excluirId !== null && $excluirId !== '') {
            $sql .= " AND id <> ?";
            $params[] = $excluirId;
        }
        $row = $this->db->query($sql, $params)->row_array();
        return $row['total'] > 0;
    }

    public function agregar($d){
        $sql = "INSERT INTO dbo.RelojDispositivo
                    (deviceId, descripcion, ip, puerto, sdk, idPlanta, grupoPlantas, zonaHoraria, fechaInicio, activo)
                VALUES (CONVERT(BIGINT, ?), ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        return $this->db->query($sql, array(
            $d['deviceId'], $d['descripcion'], $d['ip'], $d['puerto'], $d['sdk'],
            $d['idPlanta'], $d['grupoPlantas'], $d['zonaHoraria'], $d['fechaInicio'], $d['activo']
        ));
    }

    /**
     * El deviceId no se edita: es la llave de los eventos ya descargados
     * (dbo.RelojEvento). Si cambia la IP o el puerto, la descarga continúa
     * desde el último evento.
     */
    public function actualizar($id, $d){
        $sql = "UPDATE dbo.RelojDispositivo
                SET descripcion = ?, ip = ?, puerto = ?, sdk = ?, idPlanta = ?,
                    grupoPlantas = ?, zonaHoraria = ?, fechaInicio = ?, activo = ?
                WHERE id = ?";
        return $this->db->query($sql, array(
            $d['descripcion'], $d['ip'], $d['puerto'], $d['sdk'], $d['idPlanta'],
            $d['grupoPlantas'], $d['zonaHoraria'], $d['fechaInicio'], $d['activo'], $id
        ));
    }

    public function cambiarActivo($id, $activo){
        $sql = "UPDATE dbo.RelojDispositivo SET activo = ? WHERE id = ?";
        return $this->db->query($sql, array($activo, $id));
    }

    /**
     * Empleados que checaron en el reloj en una fecha: número, nombre y
     * planta (de dbo.empleados; primero el registro activo), cuántas
     * checadas y la primera / última hora.
     */
    public function empleados($id, $fecha){
        $sql = "SELECT x.numero, ISNULL(em.NOMBRE_COMPLETO, '') AS nombre,
                       ISNULL(CONVERT(VARCHAR(10), em.empresa_id) + ' - ' + emp.nombre, '') AS planta,
                       ISNULL(em.ESTATUS, '') AS estatus,
                       x.checadas, x.primera, x.ultima
                FROM (
                    SELECT CONVERT(VARCHAR(32), TRY_CONVERT(BIGINT, r.usuarioid)) AS numero,
                           COUNT(*) AS checadas,
                           CONVERT(VARCHAR(8), MIN(r.fechahora), 108) AS primera,
                           CONVERT(VARCHAR(8), MAX(r.fechahora), 108) AS ultima
                    FROM dbo.RelojEvento r
                    JOIN dbo.RelojDispositivo d ON d.deviceId = r.deviceId
                    WHERE d.id = ? AND r.esChecada = 1
                      AND r.fechahora >= CONVERT(DATE, ?) AND r.fechahora < DATEADD(DAY, 1, CONVERT(DATE, ?))
                    GROUP BY TRY_CONVERT(BIGINT, r.usuarioid)
                ) x
                OUTER APPLY (SELECT TOP 1 e.NOMBRE_COMPLETO, e.empresa_id, e.ESTATUS
                             FROM dbo.empleados e
                             WHERE TRY_CONVERT(BIGINT, e.empleado_id) = TRY_CONVERT(BIGINT, x.numero)
                             ORDER BY CASE WHEN e.ESTATUS = 'A' THEN 0 ELSE 1 END) em
                LEFT JOIN dbo.empresas emp ON emp.id = em.empresa_id
                ORDER BY x.primera";
        return $this->db->query($sql, array($id, $fecha, $fecha))->result_array();
    }

    /**
     * Checadas que trajo la última descarga del reloj: los eventos de
     * RelojEvento guardados entre ultimaDescargaInicio y ultimaDescarga.
     */
    public function bajadas($id){
        $sql = "SELECT CONVERT(VARCHAR(32), TRY_CONVERT(BIGINT, r.usuarioid)) AS numero,
                       ISNULL(em.NOMBRE_COMPLETO, '') AS nombre,
                       ISNULL(CONVERT(VARCHAR(10), em.empresa_id) + ' - ' + emp.nombre, '') AS planta,
                       CONVERT(VARCHAR(19), r.fechahora, 120) AS fechahora,
                       ISNULL(r.resultado, 'pendiente') AS resultado
                FROM dbo.RelojDispositivo d
                JOIN dbo.RelojEvento r ON r.deviceId = d.deviceId
                OUTER APPLY (SELECT TOP 1 e.NOMBRE_COMPLETO, e.empresa_id
                             FROM dbo.empleados e
                             WHERE TRY_CONVERT(BIGINT, e.empleado_id) = TRY_CONVERT(BIGINT, r.usuarioid)
                             ORDER BY CASE WHEN e.ESTATUS = 'A' THEN 0 ELSE 1 END) em
                LEFT JOIN dbo.empresas emp ON emp.id = em.empresa_id
                WHERE d.id = ? AND r.esChecada = 1
                  AND r.fechaDescarga >= d.ultimaDescargaInicio
                  AND r.fechaDescarga <= d.ultimaDescarga
                ORDER BY r.fechahora";
        return $this->db->query($sql, array($id))->result_array();
    }

    public function obtener($id){
        $sql = "SELECT id, ip, puerto FROM dbo.RelojDispositivo WHERE id = ?";
        return $this->db->query($sql, array($id))->row_array();
    }

    public function obtenerCompleto($id){
        $sql = "SELECT id, CONVERT(VARCHAR(20), deviceId) AS deviceId, descripcion, sdk, idPlanta, grupoPlantas
                FROM dbo.RelojDispositivo WHERE id = ?";
        return $this->db->query($sql, array($id))->row_array();
    }

    /**
     * Situación de cada número de empleado (los números se repiten entre empresas): el renglón que
     * se muestra es el activo, de preferencia de las plantas del reloj.
     *   activo     = 1 si está activo en CUALQUIER empresa (no se debe quitar de los relojes)
     *   activoAqui = 1 si está activo en alguna planta del reloj (debería estar en el reloj)
     * $plantas: arreglo de enteros ya validados.
     */
    public function estadoEmpleados(array $plantas){
        $in = implode(',', array_map('intval', $plantas));
        $sql = ";WITH e AS (
                    SELECT CONVERT(VARCHAR(32), TRY_CONVERT(BIGINT, em.empleado_id)) AS num,
                           em.NOMBRE_COMPLETO, em.empresa_id,
                           ROW_NUMBER() OVER (PARTITION BY TRY_CONVERT(BIGINT, em.empleado_id)
                                              ORDER BY CASE WHEN em.ESTATUS = 'A' THEN 0 ELSE 1 END,
                                                       CASE WHEN em.empresa_id IN ($in) THEN 0 ELSE 1 END) AS rn,
                           MAX(CASE WHEN em.ESTATUS = 'A' THEN 1 ELSE 0 END) OVER (PARTITION BY TRY_CONVERT(BIGINT, em.empleado_id)) AS activo,
                           MAX(CASE WHEN em.ESTATUS = 'A' AND em.empresa_id IN ($in) THEN 1 ELSE 0 END)
                               OVER (PARTITION BY TRY_CONVERT(BIGINT, em.empleado_id)) AS activoAqui
                    FROM dbo.empleados em
                    WHERE TRY_CONVERT(BIGINT, em.empleado_id) IS NOT NULL
                )
                SELECT e.num, LTRIM(RTRIM(e.NOMBRE_COMPLETO)) AS nombre, e.empresa_id, ISNULL(p.nombre, '') AS planta,
                       e.activo, e.activoAqui
                FROM e LEFT JOIN dbo.empresas p ON p.id = e.empresa_id
                WHERE e.rn = 1";
        return $this->db->query($sql)->result_array();
    }

    public function deviceIdDe($id){
        $row = $this->db->query("SELECT CONVERT(VARCHAR(20), deviceId) AS deviceId FROM dbo.RelojDispositivo WHERE id = ?", array($id))->row_array();
        return $row ? $row['deviceId'] : null;
    }
}
