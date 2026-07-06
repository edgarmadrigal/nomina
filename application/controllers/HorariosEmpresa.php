<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HorariosEmpresa extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('HorarioEmpresa');
        header('Access-Control-Allow-Origin: *');
    }

    public function index() {
        header('Access-Control-Allow-Origin: *');
        $data = [];

        if (isset($this->session->userdata['login'])) {
            $data['user'] = $this->session->userdata['login']['user'];

            if ($this->session->userdata['login']['exito']) {
                $data['empresas'] = $this->HorarioEmpresa->consultaPlanta();
                $this->load->view('header', $data);
                $this->load->view('Horarios_Empresa', $data);
                $this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
        }
    }

    public function consultaPlanta() {
        header('Access-Control-Allow-Origin: *');
        echo json_encode($this->HorarioEmpresa->consultaPlanta());
    }

    public function consultaHorario() {
        header('Access-Control-Allow-Origin: *');
        $empresa_id = $this->input->post('empresa_id', true);
        $idDia = $this->input->post('dia', true);
        echo json_encode($this->HorarioEmpresa->consultaHorario($empresa_id, $idDia));
    }

    public function consultaHorarioEditar() {
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id', true);
        echo json_encode($this->HorarioEmpresa->consultaHorarioEditar($id));
    }

    public function consultaHorarioID() {
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id', true);
        echo json_encode($this->HorarioEmpresa->consultaHorarioID($id));
    }

    public function consultaHorarios() {
        header('Access-Control-Allow-Origin: *');
        echo json_encode($this->HorarioEmpresa->consultaHorarios());
    }

    public function ActualizarHorariosEmpresa() {
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id', true);
        $empresa_id = $this->input->post('empresa_id', true);
        $fechaAsignacion = $this->input->post('fechaAsignacion', true);
        $data = array(
            'idhorario_lunes'      => $this->input->post('idhorarioLunes', true),
            'idhorario_martes'     => $this->input->post('idhorarioMartes', true),
            'idhorario_miercoles'  => $this->input->post('idhorarioMiercoles', true),
            'idhorario_jueves'     => $this->input->post('idhorarioJueves', true),
            'idhorario_viernes'    => $this->input->post('idhorarioViernes', true),
            'idhorario_sabado'     => $this->input->post('idhorarioSabado', true),
            'idhorario_domingo'    => $this->input->post('idhorarioDomingo', true),
            'descripcion'          => $this->input->post('descripcion', true),
            'fechaAsignacion'      => $fechaAsignacion,
            'cantidad'             => $this->input->post('cantidad', true)
        );

        if ($empresa_id && $fechaAsignacion) {
            echo $this->HorarioEmpresa->ActualizarHorariosEmpresaPorEmpresa($empresa_id, $data, $fechaAsignacion);
            return;
        }

        echo $this->HorarioEmpresa->ActualizarHorariosEmpresa($id, $data);
    }

    public function AgregarHorariosEmpresa() {
        header('Access-Control-Allow-Origin: *');
        // Si se envía empresa_id, aplicamos el horario a todos los empleados activos de la empresa
        $empresa_id = $this->input->post('empresa_id', true);
        $inserted = 0;
        $data_template = array(
            'idhorario_lunes'      => $this->input->post('idhorarioLunes', true),
            'idhorario_martes'     => $this->input->post('idhorarioMartes', true),
            'idhorario_miercoles'  => $this->input->post('idhorarioMiercoles', true),
            'idhorario_jueves'     => $this->input->post('idhorarioJueves', true),
            'idhorario_viernes'    => $this->input->post('idhorarioViernes', true),
            'idhorario_sabado'     => $this->input->post('idhorarioSabado', true),
            'idhorario_domingo'    => $this->input->post('idhorarioDomingo', true),
            'idEstatus'            => 1,
            'descripcion'          => $this->input->post('descripcion', true),
            'fechaAsignacion'      => $this->input->post('fechaAsignacion', true),
            'cantidad'             => $this->input->post('cantidad', true)
        );

        //if ($empresa_id) {
            // Use stored procedure to insert for all employees in the company
            $params = array(
                $empresa_id,
                $data_template['idhorario_lunes'],
                $data_template['idhorario_martes'],
                $data_template['idhorario_miercoles'],
                $data_template['idhorario_jueves'],
                $data_template['idhorario_viernes'],
                $data_template['idhorario_sabado'],
                $data_template['idhorario_domingo'],
                $data_template['idEstatus'],
                $data_template['descripcion'],
                $data_template['fechaAsignacion'],
                $data_template['cantidad']
            );

            $sp = "EXEC dbo.AgregarHorariosEmpresaSP ?,?,?,?,?,?,?,?,?,?,?,?";
            $res = $this->db->query($sp, $params);
            echo $res ? 1 : 0;
            return;
        //}

        // Si viene empleado_id individual, lo insertamos normalmente
        /*$empleado_id = $this->input->post('empleado_id', true);
        if ($empleado_id) {
            $data = $data_template;
            $data['empleado_id'] = $empleado_id;
            echo $this->HorarioEmpresa->AgregarHorariosEmpresa($data);
            return;
        }
*/
        echo 0;
    }

    public function delete() {
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id', true);
        echo $this->HorarioEmpresa->delete($id);
    }
}
