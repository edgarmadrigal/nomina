<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class controllerDiasFestivos extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('DiasFestivosModel');
    }

    public function index(){
        header('Access-Control-Allow-Origin: *');
        $data = [];
        if (isset($this->session->userdata['login'])) {
            $data['user'] = $this->session->userdata['login']['user'];

            if ($this->session->userdata['login']['exito']) {
                $this->load->view('header', $data);
                $this->load->view('diasFestivosView', $data);
                $this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
        }
    }

    public function listar(){
        header('Access-Control-Allow-Origin: *');
        $anio = $this->input->post('anio');
        $data = $this->DiasFestivosModel->listar($anio);
        echo json_encode($data);
    }

    public function guardar(){
        header('Access-Control-Allow-Origin: *');
        $fecha = $this->input->post('fecha');
        $descripcion = trim((string) $this->input->post('descripcion'));

        if (empty($fecha) || $descripcion === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'Fecha y descripción son obligatorias.']);
            return;
        }

        if ($this->DiasFestivosModel->existeFecha($fecha)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Ya existe un festivo registrado en esa fecha.']);
            return;
        }

        $this->DiasFestivosModel->agregar($fecha, $descripcion);
        echo json_encode(['exito' => true, 'mensaje' => 'Día festivo agregado correctamente.']);
    }

    public function actualizar(){
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id');
        $fecha = $this->input->post('fecha');
        $descripcion = trim((string) $this->input->post('descripcion'));

        if (empty($id) || empty($fecha) || $descripcion === '') {
            echo json_encode(['exito' => false, 'mensaje' => 'Faltan datos.']);
            return;
        }

        if ($this->DiasFestivosModel->existeFecha($fecha, $id)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Ya existe otro festivo registrado en esa fecha.']);
            return;
        }

        $this->DiasFestivosModel->actualizar($id, $fecha, $descripcion);
        echo json_encode(['exito' => true, 'mensaje' => 'Día festivo actualizado correctamente.']);
    }

    public function eliminar(){
        header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id');

        if (empty($id)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Falta el id.']);
            return;
        }

        $this->DiasFestivosModel->eliminar($id);
        echo json_encode(['exito' => true, 'mensaje' => 'Día festivo eliminado.']);
    }

    /**
     * Genera (vía el SP dbo.GeneraDiasFestivos) los 7 festivos
     * oficiales del año indicado. El proc no duplica fechas ya
     * existentes, así que es seguro correrlo más de una vez.
     */
    public function generar(){
        header('Access-Control-Allow-Origin: *');
        $anio = $this->input->post('anio');

        if (empty($anio)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Falta el año.']);
            return;
        }

        $this->DiasFestivosModel->generarAnio($anio);
        echo json_encode(['exito' => true, 'mensaje' => 'Festivos generados para ' . $anio . '.']);
    }
}
