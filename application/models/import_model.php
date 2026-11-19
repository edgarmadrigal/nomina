
<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
 
class Import_model extends CI_Model {

    public function importData($data) {
        $res = $this->db->insert_batch('checadas',$data);

        
        if($res){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    public function buscarChecadas($fecha,$hora, $usuarioid){
        if((isset($fecha))&&(isset($usuarioid))&&(isset($hora))){
            //$pass = md5($password);
            $login = $this->db->select('fecha,hora,usuarioid')
                    ->from('checadas')
                    ->where('fecha',$fecha)
                    ->where('hora',$hora)
                    ->where('usuarioid',$usuarioid)
                    ->get()
                    ->row();         

            if(isset($login->usuarioid)){
                $login = false;
            } else {
                $login = true;
            }
        } else {
            $login = true;
        }
        return $login;
    }
    
    
    public function mapaEmpleadosGrupo(array $empresas) {
    $rows = $this->db->select('empleado_id, empresa_id')
        ->from('empleados')
        ->where_in('empresa_id', $empresas)
        ->where('ESTATUS', 'A')
        ->get()->result_array();

    $mapa = [];
    $duplicados = [];
    foreach ($rows as $r) {
        $num = (int)$r['empleado_id'];
        if (isset($mapa[$num]) && $mapa[$num] != $r['empresa_id']) {
            $duplicados[$num] = true;   // mismo número en 2 empresas del grupo
        }
        $mapa[$num] = $r['empresa_id'];
    }
    return ['mapa' => $mapa, 'duplicados' => $duplicados];
}


public function checadasExistentes($desde, $hasta) {
    $rows = $this->db->select('id, usuarioid, fechahora, idPlanta')
        ->from('checadas')
        ->where('fechahora >=', $desde)
        ->where('fechahora <=', $hasta)
        ->get()->result_array();

    $existentes = [];
    foreach ($rows as $r) {
        $key = (int)trim($r['usuarioid']) . '|' . date('Y-m-d H:i', strtotime($r['fechahora']));
        $existentes[$key][] = array(
            'id'       => $r['id'],
            'idPlanta' => $r['idPlanta'],
        );
    }
    return $existentes;
}

public function corregirPlanta(array $ids, $idPlanta) {
    foreach (array_chunk($ids, 1000) as $chunk) {
        $this->db->where_in('id', $chunk)
                 ->update('checadas', array('idPlanta' => $idPlanta));
    }
}
}
 
?>
