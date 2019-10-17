<?php
//MODELO usuario
class Perfil extends CI_Model{

    function ___construct(){
        parent::___construct();
    }

    public function consulta_Perfiles(){
        $perfiles = $this->db->select('id,perfil')
        ->from('perfiles')
        ->get()
        ->result();                 
        return $perfiles;
    }   

    function get_perfil($id){
        $perfil = $this->db->select('*')
                                ->from('perfiles')
                                ->where('id',$id)
                                ->get()
                                ->row();

        return $perfil;
    }
    
    function delete_perfil($id){
        $this->db->delete('perfiles',array('id' => $id));
        
        return true;
    }
    
    function update_perfil($id,$data){

        $this->db->where('id', $id)
              ->update('perfiles', $data);
            return true;              
    }

    
    public function insert_perfil($data){   
        
       $perfil =   [
       'nombre'     =>  $data['nombre'],
       'telefono'   =>  $data['telefono'],
       'id_unidad'     =>  $data['id_unidad']
           ];   
       $d=true;
        
       $this->db->insert('perfiles',$perfil);
           
       return $d;
   }


}

?>