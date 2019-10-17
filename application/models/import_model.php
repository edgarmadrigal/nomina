
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
 
}
 
?>
