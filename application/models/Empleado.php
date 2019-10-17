<?php
//MODELO usuario
class Empleado extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */
    public function consulta($empresa_id)
    {   
        ini_set('memory_limit','512M'); // This also needs to be increased in some cases. Can be changed to a higher value as per need)
        ini_set('sqlsrv.ClientBufferMaxKBSize','524288'); // Setting to 512M
        ini_set('pdo_sqlsrv.client_buffer_max_kb_size','524288'); // Setting to 512M - for pdo_sqlsrv
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
                
        $this->db->select('e.id
        ,empleado_id
        ,NOMBRE_COMPLETO
        ,NOMBRE_PUESTO
        ,NOMBRE_DEPARTAMENTO
        ,e.ESTATUS');       
        $this->db->from('empleados e')->where('empresa_id',$empresa_id);
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