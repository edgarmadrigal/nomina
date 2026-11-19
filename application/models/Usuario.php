<?php
//MODELO usuario
class Usuario extends CI_Model{

    function ___construct()
    {
        parent::___construct();
		$this->load->library('form_validation');
    }
/**----------------------------------------------------------------------------  */
    public function consulta()
    {
        $this->db->select("u.id,u.nombre,u.usuario,p.perfil,e.nombre as planta,
            (SELECT COUNT(*) FROM dbo.UsuarioEmpresa ue WHERE ue.idUsuario = u.id AND ue.idEmpresa <> u.idPlanta) AS otrasPlantas,
            (SELECT COUNT(*) FROM dbo.UsuarioEmpresa ue WHERE ue.idUsuario = u.id) AS nPlantas", false);
        $this->db->from('usuarios u');
        $this->db->join('perfiles p', 'u.idPerfil = p.id');
        $this->db->join('empresas e', 'u.idPlanta = e.id');
        $aResult = $this->db->get();
        if(!$aResult->num_rows() == 1)
        {
            return false;
        }
        return $aResult->result_array();
    }

   /** ------------------------------------------------------------------------ */     
    public function consulta_Usuarios()
    {
        $usuarios = $this->db->select('id,nombre,usuario,idPerfil,idPlanta')
        ->from('usuarios')
        ->get()
        ->result();                 
        return $usuarios;
    }
    public function existe($usuario)
    {
        if(isset($usuario)){
            $existe = $this->db->select('usuario')
                    ->from('usuarios')
                    ->where('usuario',$usuario)
                    ->get()
                    ->num_rows();
            if($existe){
                $existe = true;
            }
            else {
                $existe = false;
            }
        } else {
            $existe = false;
        }
        return $existe;
    }

    /**
     * Revisa usuario y contraseña. Las contraseñas se guardan cifradas (bcrypt, password_hash);
     * si alguna sigue en texto plano (de antes del cambio) se compara exacta y se cifra en ese momento.
     */
    public function login_usuario($usuario, $password)
    {
        if (!is_string($usuario) || !is_string($password) || $usuario === '' || $password === '') return false;
        $fila = $this->db->select('id,nombre,usuario,password,idPerfil,idPlanta')
                ->from('usuarios')
                ->where('usuario', $usuario)
                ->get()
                ->row();
        if (!isset($fila->usuario)) {
            password_verify($password, '$2y$10$DXK3YypEXq8SQDjR/CcKRu.5K70WutbMnRHGNKcDFgTVl4CcsustG'); // mismo tiempo exista o no
            return false;
        }
        $guardada = (string) $fila->password;
        if (self::estaCifrada($guardada)) {
            $ok = password_verify($password, $guardada);
            if ($ok && password_needs_rehash($guardada, PASSWORD_DEFAULT)) $this->cambiarPassword($fila->id, $password);
        } else {
            // Antes SQL Server comparaba sin distinguir mayúsculas; se respeta esa vez y se cifra
            // tal como la escribió la persona (de ahí en adelante sí distingue).
            $ok = $guardada !== '' && hash_equals(mb_strtolower($guardada, 'UTF-8'), mb_strtolower($password, 'UTF-8'));
            if ($ok) $this->cambiarPassword($fila->id, $password);
        }
        if (!$ok) return false;
        unset($fila->password);
        $fila->login = $fila;
        $fila->loged = true;
        return $fila;
    }

    public static function estaCifrada($valor)
    {
        return is_string($valor) && strlen($valor) >= 60 && strpos($valor, '$2y$') === 0;
    }

    public function cambiarPassword($id, $password)
    {
        // En el login todavía no hay sesión: la bitácora lo registra a nombre de la persona que entra
        if (class_exists('BitacoraContexto') && empty($this->session->userdata['login']['exito'])) {
            $u = $this->db->select('usuario')->from('usuarios')->where('id', (int) $id)->get()->row();
            BitacoraContexto::poner_valores($this->db, ($u ? $u->usuario : '?') . ' (al iniciar sesión)', (int) $id,
                isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null, 'home/login');
        }
        $this->db->where('id', (int) $id)->update('usuarios', array('password' => password_hash($password, PASSWORD_DEFAULT)));
        return true;
    }

    function get_usuario($id)
    {
        $usuario = $this->db->select('id,nombre,usuario,idPerfil,idPlanta')
                                ->from('usuarios')
                                ->where('id',$id)
                                ->get()
                                ->row();
        return $usuario;
    }
    
    function delete_usuario($id)
    {
        $this->db->delete('usuarios',array('id' => $id));        
        return true;
    }
    
    function update_usuario($id,$data)
    {
        // contraseña vacía = no se cambia; si viene, se guarda cifrada
        if (!isset($data['password']) || $data['password'] === '' || $data['password'] === null) unset($data['password']);
        else $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

        $this->db->where('id', $id)
              ->update('usuarios', $data);
            return true;              
    }    
    public function insert_usuario($data)
    {   
        
       $usuario =   [
       'nombre'     =>  $data['nombre'],
       'usuario'   =>  $data['usuario'],
       'password'     =>  password_hash($data['password'], PASSWORD_DEFAULT),       
       'idPerfil'     =>  $data['idPerfil'],      
       'idPlanta'     =>  $data['idPlanta'],
       ];   
       $d=true;        
       $this->db->insert('usuarios',$usuario);
           
       return $d;
   }

}

?>