<?php
class Checada extends CI_Model{
    private $mysql; 
    function ___construct(){
        parent::___construct();
        $this->load->library('form_validation');
        //$this->load->library('pedeo', [$this->mysql]);
    }
    public function consulta($NoSemana,$anio,$departamento,$noempleado)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaChecadas ?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => $anio,
        'Code' => $departamento,
        'NoEmpleado' => $noempleado);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }
    public function consultaReprocesar($NoSemana,$anio,$departamento,$noempleado)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaChecadasProceso ?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => $anio,
        'Code' => $departamento,
        'NoEmpleado' => $noempleado);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }

    public function consultaDepartamento ()
    {
        $sp = "ConsultaClasificaciones";        
        $result = $this->db->query($sp);
        return  $result->result_array();
    }
    //CONEXION DE MYSQL(MARIADB) a BIOSTAR 
    function consultaChecadasMYSQL() {
        $mysql = $this->load->database('another_db', TRUE);
        $mysql->select("USRID,evtlguid, devuid, date_format(srvdt,'%H:%i:%S') as hora, date_format(srvdt,'%Y-%m-%d') as fecha")
              ->from('biostar2_ac.t_lg201812')
              ->where('usrid >','0');
        $query = $mysql->get();
        return $query->result_array();
    }

    public function existe($checada){
        if(isset($checada)){
            $existe = $this->db->select('checada')
                    ->from('checadas')
                    ->where('checada',$checada)
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

    public function login_usuario($checada, $password){
        if((isset($checada))&&(isset($password))){
            //$pass = md5($password);
            $login = $this->db->select('id,nombre,checada')
                    ->from('checadas')
                    ->where('checada',$checada)
                    ->where('password',$password)
                    ->get()
                    ->row();         

            if(isset($login->checada)){
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

    function get_usuario($id) {
        $checada = $this->db->select('*')
                                ->from('checadas')
                                ->where('id',$id)
                                ->get()
                                ->row();
        return $checada;
    }

    function delete_usuario($id){
        $this->db->delete('checadas',array('id' => $id));        
        return true;
    }
    
    function update_usuario($id,$data){

        $this->db->where('id', $id)
              ->update('checadas', $data);
            return true;              
    }

    public function insert_usuario($data){   
        
       $checada =   [
       'nombre'     =>  $data['nombre'],
       'checada'   =>  $data['checada'],
       'password'     =>  $data['password'],       
       'idPerfil'     =>  $data['idPerfil']
       ];   
       $d=true;        
       $this->db->insert('checadas',$checada);
           
       return $d;
    }

}

?>