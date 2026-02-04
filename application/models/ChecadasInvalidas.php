<?php
class ChecadasInvalidas extends CI_Model{
    private $mysql; 
    function ___construct(){
        parent::___construct();
        $this->load->library('form_validation');
        //$this->load->library('pedeo', [$this->mysql]);
    }
    public function consulta($NoSemana,$anio,$departamento,$noempleado)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaChecadasinvalidas ?,?,?,? "; 
        $params = array(
        'NoSemana' => $NoSemana,
        'anio' => $anio,
        'Code' => $departamento,
        'NoEmpleado' => $noempleado);
        
        $result = $this->db->query($sp,$params);
        return  $result->result_array();
    }

    

    public function ConsultaChecadasinvalidasActivosInactivos($NoSemana,$anio,$departamento,$noempleado)
    {
        ini_set('max_execution_time', 0); //para que no limite a 30 segundos la consulta
        $sp = "ConsultaChecadasinvalidasActivosInactivos ?,?,?,? "; 
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
        $sp = "ConsultaChecadasinvalidasProceso ?,?,?,? "; 
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
    function consultaChecadasinvalidasMYSQL() {
        $mysql = $this->load->database('another_db', TRUE);
        $mysql->select("USRID,evtlguid, devuid, date_format(srvdt,'%H:%i:%S') as hora, date_format(srvdt,'%Y-%m-%d') as fecha")
              ->from('biostar2_ac.t_lg201812')
              ->where('usrid >','0');
        $query = $mysql->get();
        return $query->result_array();
    }

    public function existe($ChecadasInvalidas){
        if(isset($ChecadasInvalidas)){
            $existe = $this->db->select('ChecadasInvalidas')
                    ->from('Checadasinvalidas')
                    ->where('ChecadasInvalidas',$ChecadasInvalidas)
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

    public function login_usuario($ChecadasInvalidas, $password){
        if((isset($ChecadasInvalidas))&&(isset($password))){
            //$pass = md5($password);
            $login = $this->db->select('id,nombre,ChecadasInvalidas')
                    ->from('Checadasinvalidas')
                    ->where('ChecadasInvalidas',$ChecadasInvalidas)
                    ->where('password',$password)
                    ->get()
                    ->row();         

            if(isset($login->ChecadasInvalidas)){
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
        $ChecadasInvalidas = $this->db->select('*')
                                ->from('Checadasinvalidas')
                                ->where('id',$id)
                                ->get()
                                ->row();
        return $ChecadasInvalidas;
    }

    function delete_usuario($id){
        $this->db->delete('Checadasinvalidas',array('id' => $id));        
        return true;
    }
    
    function update_usuario($id,$data){

        $this->db->where('id', $id)
              ->update('Checadasinvalidas', $data);
            return true;              
    }

    public function insert_usuario($data){   
        
       $ChecadasInvalidas =   [
       'nombre'     =>  $data['nombre'],
       'ChecadasInvalidas'   =>  $data['ChecadasInvalidas'],
       'password'     =>  $data['password'],       
       'idPerfil'     =>  $data['idPerfil']
       ];   
       $d=true;        
       $this->db->insert('Checadasinvalidas',$ChecadasInvalidas);
           
       return $d;
    }

}

?>