<?php
//MODELO horarios
class Horario extends CI_Model{

    function ___construct(){
        parent::___construct();
		//$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */
    public function consulta()
    {
        $this->db->select('id
                        ,nombre
                        ,entrada_Desde
                        ,entrada_Inicio
                        ,entrada_Hasta
                        ,salida_Desde
                        ,salida_Inicio
                        ,salida_Hasta
                        ,horas_diarias
                        ,comedor
                        ,tiempoComida
                        ,tipo
                        ,fechaAsignacion');
        $this->db->from('horarios')
        ->where('status','1');
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
    }

   /** ------------------------------------------------------------------------ */     
    public function consulta_Horarios(){
        $usuarios = $this->db->select('id
                                    ,nombre
                                    ,entrada_Desde
                                    ,entrada_Inicio
                                    ,entrada_Hasta
                                    ,salida_Desde
                                    ,salida_Inicio
                                    ,salida_Hasta
                                    ,horas_diarias
                                    ,comedor
                                    ,tiempoComida
                                    ,tipo
                                    ,fechaAsignacion')
        ->from('horarios')
        ->where('status','1')
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

    function get_horario($id){
        $usuario = $this->db->select('id
                                    ,nombre
                                    ,entrada_Desde
                                    ,entrada_Inicio
                                    ,entrada_Hasta
                                    ,salida_Desde
                                    ,salida_Inicio
                                    ,salida_Hasta
                                    ,horas_diarias
                                    ,comedor
                                    ,tiempoComida
                                    ,tipo
                                    ,fechaAsignacion')
                                ->from('horarios')
                                ->where('id',$id)
                                ->get()
                                ->row();
        return $usuario;
    }
    
    function delete_horario($id){
        $this->db->delete('horarios',array('id' => $id));        
        return true;
    }
    
    function update_horario($id,$data){

        $this->db->where('id', $id)->update('horarios', $data);
            return true;              
    }    
    public function insert_horario($data){
        
       $horario =   [
        'nombre' 	        => $data['nombre'] ,	
        'entrada_Inicio' 	=> $data['entrada_Inicio'  ] ,	
        'salida_Desde' 		=> $data['salida_Desde'    ] ,	
        'salida_Hasta' 		=> $data['salida_Hasta'    ] ,	
        'entrada_Desde' 	=> $data['entrada_Desde'   ] ,	
        'entrada_Hasta' 	=> $data['entrada_Hasta'   ] ,	
        'salida_Inicio' 	=> $data['salida_Inicio'   ] ,	
        'horas_diarias' 	=> $data['horas_diarias'   ] ,
        'comedor'			=> $data['comedor'         ] ,
        'tiempoComida'      => $data['tiempoComida'    ] ,
        'tipo' 				=> $data['tipo'            ] ,
        'fechaAsignacion'	=> $data['fechaAsignacion' ] ,	
        'status' 	        => 1,
       ];   
       $d=true;        
       $this->db->insert('horarios',$horario);
           
       return $d;
    }

}

?>