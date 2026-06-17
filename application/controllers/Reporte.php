<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reporte extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(['form', 'url']);
        $this->load->library('session');
        $this->load->model('Reporte_model');
    }

    public function index() {
        header('Access-Control-Allow-Origin: *');
		$data = [];
		$this->load->model('Usuario');
		$this->load->model('Horario');
        if (isset($this->session->userdata['login'])) {
			$data['user'] = $this->session->userdata['login']['user'];
			$data['horarios'] = $this->Horario->consulta_Horarios();
			
            if ($this->session->userdata['login']['exito']) {
				$this->load->view('header', $data);
                $this->load->view('reporte_unica');
				$this->load->view('footer', $data);
            } else {
                $this->load->view('login');
            }
        } else {
            $this->load->view('login');
				}		
    }

    public function procesar_csv() {
        $config['upload_path']   = './uploads/';
        $config['allowed_types'] = 'csv';
        $config['max_size']      = 2048; // 2 MB
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('archivo_csv')) {
            $error = ['error' => $this->upload->display_errors()];
            header('Access-Control-Allow-Origin: *');
                $data = [];
                $this->load->model('Usuario');
                $this->load->model('Horario');
                if (isset($this->session->userdata['login'])) {
                    $data['user'] = $this->session->userdata['login']['user'];
                    $data['horarios'] = $this->Horario->consulta_Horarios();
                    
                    if ($this->session->userdata['login']['exito']) {
                        $this->load->view('header', $data);
            $this->load->view('reporte_unica', $error);
                        $this->load->view('footer', $data);
                    } else {
                        $this->load->view('login');
                    }
                } else {
                    $this->load->view('login');
                        }		
        } else {
            $file_data = $this->upload->data();
            $file_path = $file_data['full_path'];

            $lineas = file($file_path, FILE_IGNORE_NEW_LINES); // mantenemos líneas vacías
            $procesadas = [];
            $linea_num = 0;

            foreach ($lineas as $fila) {
                $linea_num++;
                $fila = $this->Reporte_model->procesar_linea($fila, $linea_num);
                $procesadas[] = $fila;
            }

            $this->session->set_userdata('csv_procesado', $procesadas);
            $data['lineas'] = $procesadas;
            $this->load->view('reporte_unica', $data);
        }
    }

    public function descargar_csv() {
        $lineas = $this->session->userdata('csv_procesado');

        if (!$lineas) redirect('reporte');

        $nombre_archivo = "reporte_" . date("Y-m-d") . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM UTF-8

        foreach ($lineas as $fila) {
            fwrite($salida, $fila . "\n");
        }

        fclose($salida);
        exit;
    }
}