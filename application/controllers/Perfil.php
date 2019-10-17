<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Perfil extends CI_Controller {
    
    function consulta()
    {
		header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id');
        echo json_encode($this->EmpleadoModel->get_empleado($id));
    }

    function edit()
    {
		header('Access-Control-Allow-Origin: *');
        $id = $this->input->post('id');
        $data = [
            'nombre' => $this->input->post('nombre'),
            'telefono' => $this->input->post('telefono'),
            'id_unidad' => $this->input->post('id_unidad'),
        ];
        echo $this->EmpleadoModel->update_empleado($id, $data);
    }

    function delete()
    {
        $id = $this->input->post('id');
        echo $this->EmpleadoModel->delete_empleado($id);
    }

    function insert()
    {
		header('Access-Control-Allow-Origin: *');
        $data = [
            'nombre' => $this->input->post('nombre'),
            'telefono' => $this->input->post('telefono'),
            'id_unidad' => $this->input->post('id_unidad')
        ];

        $dt['exito'] = $this->EmpleadoModel->insert_empleado($data);
        echo $dt['exito'];
    }
}
?>