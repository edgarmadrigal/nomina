<?php
//MODELO usuario
class ByteEmpleado extends CI_Model{

    function ___construct(){
        parent::___construct();
		$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */
    public function consulta($empresa)
    {
        $empleados=null;
        if($empresa==1){
            $empleados= $this->db->select('EMPLEADO_ID as ID
                                        ,NO_BYTE AS BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_NAZARENO1');       
        }else if($empresa==2){
            $empleados= $this->db->select('EMPLEADO_ID as ID
                                        ,NO_BYTE AS BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_NAZARENO2');   
        }else if($empresa==3){
            $empleados= $this->db->select('EMPLEADO_ID as ID
                                        ,NO_BYTE AS BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_NAZARENO3');   
        }  else if($empresa==4){
            $empleados= $this->db->select('EMPLEADO_ID as ID
                                        ,NO_BYTE AS BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_CUENCAME');   
        }              
        $empleados = $this->db->get();
        if(!$empleados->num_rows() == 1)
        {
            return false;
        }
        return $empleados->result_array();
    }

   /** ------------------------------------------------------------------------ */     
    public function consulta_Empleados_Nazareno($empresa){
        $empleados=null;
        if($empresa==1){
            $empleados= $this->db->select('EMPLEADO_ID
                                        ,NO_BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_NAZARENO1')
            ->get()
            ->result();       
        }else if($empresa==2){
            $empleados= $this->db->select('EMPLEADO_ID
                                        ,NO_BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_NAZARENO2')
            ->get()
            ->result();   
        }else if($empresa==3){
            $empleados= $this->db->select('EMPLEADO_ID
                                        ,NO_BYTE
                                        ,NUMERO
                                        ,NOMBRE_COMPLETO
                                        ,APELLIDO_PATERNO
                                        ,APELLIDO_MATERNO
                                        ,NOMBRES
                                        ,ESTATUS')
            ->from('Nominas.dbo.empleadosByte_NAZARENO3')
            ->get()
            ->result();   
        }             
        return $empleados;
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
            $login = $this->db->select('id,nombre,usuario')
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
        $usuario = $this->db->select('*')
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