<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permisos por usuario (application/sql/permisos_usuarios.sql):
 *   dbo.MenuOpcion     opciones del menú lateral por sección
 *   dbo.UsuarioPermiso qué opciones ve cada usuario
 *   dbo.UsuarioEmpresa empresas que puede consultar (sin renglones = todas)
 */
class PermisoModel extends CI_Model {

    // Orden en que se pintan las secciones del menú
    public static $secciones = array('General', 'Catalogos', 'Reportes', 'Importar/Exportar');

    const EMPRESA_EVENTUALES = 25;
    const OPCION_EVENTUALES  = 'EmpleadosController_Eventuales';

    // Perfil Invitados: solo la música (radio y YouTube) y el chat, sin checadas, asistencias ni catálogos
    const PERFIL_INVITADO = 4;

    function __construct(){
        parent::__construct();
    }

    public static function esInvitado($user){
        return $user && isset($user->idPerfil) && (int) $user->idPerfil === self::PERFIL_INVITADO;
    }

    /**
     * Opciones que ve el usuario, agrupadas por sección.
     * El administrador (idPerfil = 1) siempre ve "Usuarios" para no quedarse sin acceso.
     */
    public function menuUsuario($user){
        $porSeccion = array();
        if (!isset($user->id) || self::esInvitado($user)) return $porSeccion;

        $sql = "SELECT o.seccion, o.nombre, o.url, o.icono
                FROM dbo.MenuOpcion o
                WHERE o.activo = 1
                  AND (EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p WHERE p.idOpcion = o.id AND p.idUsuario = ?)
                       OR (o.url = 'usuarios' AND ? = 1))
                ORDER BY o.orden";
        $perfil = isset($user->idPerfil) ? (int) $user->idPerfil : 0;
        foreach ($this->db->query($sql, array($user->id, $perfil))->result_array() as $o) {
            $porSeccion[$o['seccion']][] = $o;
        }
        return $porSeccion;
    }

    public function opciones(){
        $sql = "SELECT id, seccion, nombre, url FROM dbo.MenuOpcion WHERE activo = 1 ORDER BY orden";
        return $this->db->query($sql)->result_array();
    }

    public function empresas(){
        $sql = "SELECT id, nombre FROM dbo.empresas WHERE estatus = 1 ORDER BY nombre";
        return $this->db->query($sql)->result_array();
    }

    /** Para "copiar permisos de otro usuario". */
    public function usuarios(){
        return $this->db->query("SELECT id, usuario, nombre FROM dbo.usuarios ORDER BY usuario")->result_array();
    }

    /**
     * ids de opciones y de empresas asignadas al usuario ('empresas' vacío = todas).
     * Para FILTRAR (por omisión) nunca incluye las empresas restringidas que el usuario no puede ver
     * (IBF): si no tiene plantas asignadas pero hay restringidas ocultas, 'empresas' trae la lista
     * explícita de todas las demás, así los "vacío = todas" de los reportes no la dejan pasar.
     * $paraEditar = true regresa lo guardado tal cual (pantalla de permisos del usuario).
     */
    public function permisosUsuario($idUsuario, $paraEditar = false){
        $op = $this->db->query("SELECT idOpcion FROM dbo.UsuarioPermiso WHERE idUsuario = ?", array($idUsuario))->result_array();
        $em = $this->db->query("SELECT idEmpresa FROM dbo.UsuarioEmpresa WHERE idUsuario = ?", array($idUsuario))->result_array();
        $empresas = array_map('intval', array_column($em, 'idEmpresa'));
        if (!$paraEditar) {
            $ocultas = $this->empresasOcultas($idUsuario);
            if ($ocultas) {
                if (!$empresas) {
                    $todas = $this->db->query("SELECT id FROM dbo.empresas")->result_array();
                    $empresas = array_map('intval', array_column($todas, 'id'));
                }
                $empresas = array_values(array_diff($empresas, $ocultas));
            }
            if ($empresas && $this->tieneEventuales($idUsuario) && !in_array(self::EMPRESA_EVENTUALES, $empresas, true)) {
                $empresas[] = self::EMPRESA_EVENTUALES;
            }
        }
        return array(
            'opciones' => array_map('intval', array_column($op, 'idOpcion')),
            'empresas' => $empresas,
        );
    }

    /** Tiene el permiso de Catálogos > Empleados Eventuales. */
    public function tieneEventuales($idUsuario){
        $r = $this->db->query(
            "SELECT COUNT(*) AS n FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
             WHERE p.idUsuario = ? AND o.activo = 1 AND o.url = ?",
            array((int) $idUsuario, self::OPCION_EVENTUALES))->row_array();
        return $r && (int) $r['n'] > 0;
    }

    /**
     * EVENTUALES tiene estatus 0 (no se sincroniza con Microsip), por eso no viene en los combos:
     * se agrega a la lista (arreglos con id y nombre) solo a quien tiene el permiso de Empleados Eventuales.
     */
    public function agregarEventuales($idUsuario, array $plantas){
        if (in_array(self::EMPRESA_EVENTUALES, array_map('intval', array_column($plantas, 'id')), true)) return $plantas;
        if (!$this->tieneEventuales($idUsuario)) return $plantas;
        $emp = $this->db->query("SELECT id, nombre FROM dbo.empresas WHERE id = ?", array(self::EMPRESA_EVENTUALES))->row_array();
        if (!$emp) return $plantas;
        $plantas[] = $emp;
        usort($plantas, function ($a, $b) { return strcmp($a['nombre'], $b['nombre']); });
        return $plantas;
    }

    /** Cache por petición de empresasOcultas. */
    private $ocultasCache = array();

    /**
     * Empresas restringidas (empresas.restringida = 1, p. ej. IBF) que el usuario NO puede ver: solo
     * las ve quien tiene una opción de menú que la otorga (MenuOpcion.empresaRestringida), aunque sea
     * administrador.
     */
    public function empresasOcultas($idUsuario){
        $idUsuario = (int) $idUsuario;
        if (!isset($this->ocultasCache[$idUsuario])) {
            $r = $this->db->query(
                "SELECT e.id FROM dbo.empresas e
                 WHERE e.restringida = 1
                   AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso p JOIN dbo.MenuOpcion o ON o.id = p.idOpcion
                                   WHERE p.idUsuario = ? AND o.activo = 1 AND o.empresaRestringida = e.id)",
                array($idUsuario))->result_array();
            $this->ocultasCache[$idUsuario] = array_map('intval', array_column($r, 'id'));
        }
        return $this->ocultasCache[$idUsuario];
    }

    /** Ocultas del usuario en sesión (sin sesión = todas las restringidas). */
    public function empresasOcultasSesion(){
        $CI =& get_instance();
        $u = isset($CI->session->userdata['login']['user']) ? $CI->session->userdata['login']['user'] : null;
        return $this->empresasOcultas($u && isset($u->id) ? (int) $u->id : 0);
    }

    /** El usuario en sesión puede ver esa empresa (no es una restringida sin permiso). */
    public function puedeVerEmpresa($empresaId){
        return !in_array((int) $empresaId, $this->empresasOcultasSesion(), true);
    }

    /** Quita de una lista de empresas (arreglos u objetos con 'id') las restringidas que no puede ver el usuario en sesión. */
    public function quitarOcultas(array $plantas){
        $ocultas = array_flip($this->empresasOcultasSesion());
        if (!$ocultas) return $plantas;
        return array_values(array_filter($plantas, function ($p) use ($ocultas) {
            $id = is_array($p) ? $p['id'] : $p->id;
            return !isset($ocultas[(int) $id]);
        }));
    }

    /** Reemplaza los permisos del usuario. $empresas vacío = todas las empresas. */
    public function guardar($idUsuario, array $opciones, array $empresas){
        // Solo se quita y agrega lo que cambió, para que la bitácora (dbo.Bitacora) registre
        // únicamente los permisos que de verdad se dieron o quitaron.
        $opciones = array_values(array_unique(array_map('intval', $opciones)));
        $empresas = array_values(array_unique(array_map('intval', $empresas)));
        $this->db->trans_start();
        $this->db->query("DELETE FROM dbo.UsuarioPermiso WHERE idUsuario = ?" . ($opciones ? " AND idOpcion NOT IN (" . implode(',', $opciones) . ")" : ""), array($idUsuario));
        $this->db->query("DELETE FROM dbo.UsuarioEmpresa WHERE idUsuario = ?" . ($empresas ? " AND idEmpresa NOT IN (" . implode(',', $empresas) . ")" : ""), array($idUsuario));
        foreach ($opciones as $id) {
            $this->db->query("INSERT INTO dbo.UsuarioPermiso (idUsuario, idOpcion)
                              SELECT ?, id FROM dbo.MenuOpcion WHERE id = ?
                                AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioPermiso WHERE idUsuario = ? AND idOpcion = ?)", array($idUsuario, $id, $idUsuario, $id));
        }
        foreach ($empresas as $id) {
            $this->db->query("INSERT INTO dbo.UsuarioEmpresa (idUsuario, idEmpresa)
                              SELECT ?, id FROM dbo.empresas WHERE id = ?
                                AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioEmpresa WHERE idUsuario = ? AND idEmpresa = ?)", array($idUsuario, $id, $idUsuario, $id));
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** Reemplaza solo las empresas del usuario ($empresas vacío = todas), sin tocar sus opciones del menú. */
    public function guardarEmpresas($idUsuario, array $empresas){
        $empresas = array_values(array_unique(array_filter(array_map('intval', $empresas))));
        $this->db->trans_start();
        $this->db->query("DELETE FROM dbo.UsuarioEmpresa WHERE idUsuario = ?" . ($empresas ? " AND idEmpresa NOT IN (" . implode(',', $empresas) . ")" : ""), array($idUsuario));
        foreach ($empresas as $id) {
            $this->db->query("INSERT INTO dbo.UsuarioEmpresa (idUsuario, idEmpresa)
                              SELECT ?, id FROM dbo.empresas WHERE id = ?
                                AND NOT EXISTS (SELECT 1 FROM dbo.UsuarioEmpresa WHERE idUsuario = ? AND idEmpresa = ?)", array($idUsuario, $id, $idUsuario, $id));
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function eliminarUsuario($idUsuario){
        $this->db->query("DELETE FROM dbo.UsuarioPermiso WHERE idUsuario = ?", array($idUsuario));
        $this->db->query("DELETE FROM dbo.UsuarioEmpresa WHERE idUsuario = ?", array($idUsuario));
    }

    /**
     * Filtra una lista de plantas (arreglos con 'id') a las empresas del usuario.
     * Si el usuario no tiene empresas asignadas, regresa la lista completa.
     */
    public function filtrarPlantas($idUsuario, array $plantas){
        $permisos = $this->permisosUsuario($idUsuario);   // ya sin las restringidas que no puede ver
        if (empty($permisos['empresas'])) return $plantas;
        $ok = array_flip($permisos['empresas']);
        return array_values(array_filter($plantas, function ($p) use ($ok) {
            $id = is_array($p) ? $p['id'] : $p->id;
            return isset($ok[(int) $id]);
        }));
    }
}
