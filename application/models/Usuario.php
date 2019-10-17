<?php
//MODELO usuario
class Usuario extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */
    public function consulta()
    {
        $this->db->select('u.id,u.nombre,u.usuario,p.perfil');
        $this->db->from('usuarios u');
        $this->db->join('perfiles p', 'u.idPerfil = p.id');
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
    }

   /** ------------------------------------------------------------------------ */     
    public function consulta_Usuarios(){
        $usuarios = $this->db->select('id,nombre,usuario,password,idPerfil')
        ->from('usuarios')
        ->get()
        ->result();                 
        return $usuarios;
    }
    public function existe($usuario){
        if(isset($usuario)){
            $existe = $this->db->select('usuario')
                    ->from('usuarios')
                    ->where('usuario',$usuario)
                    ->get()
                    ->num_rows();
            if($existe){
                $existe = true;
            } else {
                $existe = false;
            }
        } else {
            $existe = false;
        }
        return $existe;
    }

    public function login_usuario($usuario, $password){
        if((isset($usuario))&&(isset($password))){
            //$pass = md5($password);
            $login = $this->db->select('id,nombre,usuario,idPerfil')
                    ->from('usuarios')
                    ->where('usuario',$usuario)
                    ->where('password',$password)
                    ->get()
                    ->row();         

            if(isset($login->usuario)){
                $login->login = $login;
                $login->loged = true;
            } else {
                $login = false;
            }
        } else {
            $login = false;
        }
        return $login;
    }

    function get_usuario($id){
        $usuario = $this->db->select('id,nombre,usuario,password,idPerfil')
                                ->from('usuarios')
                                ->where('id',$id)
                                ->get()
                                ->row();
        return $usuario;
    }
    
    function delete_usuario($id){
        $this->db->delete('usuarios',array('id' => $id));        
        return true;
    }
    
    function update_usuario($id,$data){

        $this->db->where('id', $id)
              ->update('usuarios', $data);
            return true;              
    }    
    public function insert_usuario($data){   
        
       $usuario =   [
       'nombre'     =>  $data['nombre'],
       'usuario'   =>  $data['usuario'],
       'password'     =>  $data['password'],       
       'idPerfil'     =>  $data['idPerfil']
       ];   
       $d=true;        
       $this->db->insert('usuarios',$usuario);
           
       return $d;
   }

}

?>