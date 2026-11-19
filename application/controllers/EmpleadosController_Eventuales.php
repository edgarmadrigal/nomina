<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmpleadosController_Eventuales extends CI_Controller {
	
	public function construct()
	{
		 header('Access-Control-Allow-Origin: *');
		 parent::__construct();
		 $this->load->database();
		 $this->load->model('import_model', 'import');
		 $this->load->model('empleadoModel_Eventuales');
    }

    public function index(){
		header('Access-Control-Allow-Origin: *');
		$data = [];
		//$this->load->model('Asistencia');
			if (isset($this->session->userdata['login'])) {
		  $data['user'] = $this->session->userdata['login']['user'];
				if ($this->session->userdata['login']['exito']) {
				  $this->load->view('header', $data);
				  $this->load->view('EmpleadosView_Eventuales', $data);
				  $this->load->view('footer', $data);
				} else {
					$this->load->view('login');
			  }
			} else {
				$this->load->view('login');
		}
	}

/**  */
	function consulta()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        echo json_encode($this->empleadoModel_Eventuales->get_empleado($id));
    }

    public function consulta_empleado()
    {
		$result = array('data' => array());
		$this->load->model('empleadoModel_Eventuales');
		$data =$this->empleadoModel_Eventuales->consulta();
		foreach ($data as $key => $value) {
			$buttons = '
			<div class="btn-group" >
			  <button type="button" class="btn btn-default dropdown-toggle" style="background-color: #20aee3;" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
			    Acciones <span class="caret"></span>
			  </button>
			  <ul class="dropdown-menu">
			    <li><a type="button" onclick="editar('.$value['id'].')" data-toggle="modal" data-target="#myModal_agregar">Editar</a></li>
			    <li><a type="button" onclick="eliminar('.$value['id'].', '. "'".$value['NOMBRE_COMPLETO'] ."'".'  )">Eliminar</a></li>			    
			  </ul>
			</div>
			';
			$result['data'][$key] = array(
				$value['id'],		
				$value['NUMERO'],	
				$value['NOMBRE_COMPLETO'],
				$value['NOMBRE_PUESTO'],
				$value['NOMBRE_DEPARTAMENTO'],
				$value['ESTATUS'],
				$buttons
			);
		} // /foreach
		echo json_encode($result);
    }

    function edit()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        $data = [
			
	 	'empleado_id'           	=> $this->input->post('NUMERO'),  
		'NUMERO'           			=> $this->input->post('NUMERO'),
		'NOMBRE_COMPLETO'           => $this->input->post('NOMBRE_COMPLETO'),
		'NOMBRES'                	=> $this->input->post('NOMBRES'				),
		'APELLIDO_PATERNO'       	=> $this->input->post('APELLIDO_PATERNO'	),       
		'APELLIDO_MATERNO'       	=> $this->input->post('APELLIDO_MATERNO'	),       
		'NOMBRE_PUESTO'       		=> $this->input->post('NOMBRE_PUESTO'		),  
		'NOMBRE_DEPARTAMENTO'       => $this->input->post('NOMBRE_DEPARTAMENTO'	),
		'ESTATUS'       			=> 'A',
		];
        echo $this->empleadoModel_Eventuales->update_empleado($id, $data);
    }

    function delete()
    {

		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        $data = [
		'ESTATUS'                	=>  'B',  
        ];
        echo $this->empleadoModel_Eventuales->update_empleado($id, $data);

		/*
	   'NUMERO'                 =>  $data['NUMERO'],
       'empleado_id'            =>  $data['NUMERO'],
       'NOMBRES'                =>  $data['NOMBRES'],
       'APELLIDO_PATERNO'       =>  $data['APELLIDO_PATERNO'],       
       'APELLIDO_MATERNO'       =>  $data['APELLIDO_MATERNO'],       
       'APELLIDO_PATERNO'       =>  $data['NOMBRE_PUESTO'],  
       'APELLIDO_PATERNO'       =>  $data['NOMBRE_DEPARTAMENTO'],  
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $id = $this->input->post('id');
        echo $this->empleadoModel_Eventuales->delete_usuario($id);
		*/
    }

    function insert()
    {
		header('Access-Control-Allow-Origin: *');
		$this->load->model('empleadoModel_Eventuales');
        $data = [
			'NUMERO'                 	=> $this->input->post('NUMERO'				),
			'NOMBRE_COMPLETO'           => $this->input->post('NOMBRE_COMPLETO'		),
			'NOMBRES'                	=> $this->input->post('NOMBRES'				),
			'APELLIDO_PATERNO'       	=> $this->input->post('APELLIDO_PATERNO'	),       
			'APELLIDO_MATERNO'       	=> $this->input->post('APELLIDO_MATERNO'	),       
			'NOMBRE_PUESTO'       		=> $this->input->post('NOMBRE_PUESTO'		),  
			'NOMBRE_DEPARTAMENTO'       => $this->input->post('NOMBRE_DEPARTAMENTO' ),
			'ESTATUS'                	=>  'A',  
        ];

        $dt['exito'] = $this->empleadoModel_Eventuales->insert_empleado($data);
        $this->empleadoModel_Eventuales->traerChecadas(trim((string) $data['NUMERO']));
        echo $dt['exito'];
    }

	/** Machote de la alta masiva: NUMERO | NOMBRE COMPLETO (PATERNO/MATERNO; NOMBRES) | DEPARTAMENTO. */
	function machote()
	{
		require_once APPPATH . "/third_party/PHPExcel.php";
		$xls = new PHPExcel();
		$hoja = $xls->getActiveSheet();
		$hoja->setTitle('EVENTUALES');
		$hoja->fromArray(array('NUMERO', 'NOMBRE COMPLETO', 'DEPARTAMENTO'), null, 'A1');
		$hoja->fromArray(array('999999', 'PATERNO/MATERNO; NOMBRES', 'DEPARTAMENTO'), null, 'A2');
		$hoja->getStyle('A1:C1')->getFont()->setBold(true);
		$hoja->getStyle('A2:C2')->getFont()->setItalic(true)->getColor()->setRGB('888888');
		$hoja->getStyle('A:A')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
		$hoja->getColumnDimension('A')->setWidth(12);
		$hoja->getColumnDimension('B')->setWidth(45);
		$hoja->getColumnDimension('C')->setWidth(25);
		$hoja->setCellValue('E1', 'El renglón 2 es de ejemplo: bórralo o reemplázalo.');
		$hoja->setCellValue('E2', 'Nombre como en Microsip: PATERNO/MATERNO; NOMBRES');
		$hoja->freezePane('A2');
		if (ob_get_length() !== false) @ob_clean();
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="Machote_Eventuales.xlsx"');
		header('Cache-Control: max-age=0');
		PHPExcel_IOFactory::createWriter($xls, 'Excel2007')->save('php://output');
		exit;
	}

	/**
	 * Alta masiva desde el machote. Si el número ya existe en eventuales se actualizan nombre y
	 * departamento y se reactiva (ESTATUS A); si no, se da de alta. Regresa un resumen y los renglones con error.
	 */
	function importar()
	{
		$this->load->model('empleadoModel_Eventuales');
		$res = function ($d) { $this->output->set_content_type('application/json')->set_output(json_encode($d, JSON_UNESCAPED_UNICODE)); };
		if (empty($_FILES['archivo']['tmp_name']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) { $res(array('exito' => false, 'mensaje' => 'No llegó el archivo.')); return; }
		$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, array('xlsx', 'xls', 'csv'), true)) { $res(array('exito' => false, 'mensaje' => 'Debe ser un Excel (.xlsx).')); return; }

		require_once APPPATH . "/third_party/PHPExcel.php";
		try {
			$filas = PHPExcel_IOFactory::load($_FILES['archivo']['tmp_name'])->getSheet(0)->toArray(null, true, false, false);
		} catch (Exception $e) { $res(array('exito' => false, 'mensaje' => 'No se pudo leer el Excel.')); return; }

		$may = function ($s) { return mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $s)), 'UTF-8'); };
		$altas = 0; $actualizados = 0; $errores = array(); $vistos = array();
		foreach ($filas as $i => $f) {
			$renglon = $i + 1;
			$numero = preg_replace('/\D/', '', preg_replace('/\.0+$/', '', trim((string) (isset($f[0]) ? $f[0] : ''))));
			$nombre = $may(isset($f[1]) ? $f[1] : '');
			$depto  = mb_substr($may(isset($f[2]) ? $f[2] : ''), 0, 100, 'UTF-8');
			if ($numero === '' && $nombre === '') continue;                 // renglón vacío o encabezado sin número
			if ($numero === '' ) { if ($i > 0) $errores[] = "Renglón $renglon: falta el número."; continue; }
			if ($numero === '999999' && $nombre === 'PATERNO/MATERNO; NOMBRES') continue;   // ejemplo del machote
			if (strlen($numero) > 10) { $errores[] = "Renglón $renglon: el número $numero es muy largo."; continue; }
			if ($nombre === '') { $errores[] = "Renglón $renglon: falta el nombre de $numero."; continue; }
			if (isset($vistos[$numero])) { $errores[] = "Renglón $renglon: el número $numero está repetido (renglón {$vistos[$numero]})."; continue; }
			$vistos[$numero] = $renglon;

			// PATERNO/MATERNO; NOMBRES (o con coma). Sin separador se guarda todo en NOMBRES.
			$partes = preg_split('/[;,]/', $nombre, 2);
			if (count($partes) == 2) {
				$ap = explode('/', $partes[0], 2);
				$paterno = trim($ap[0]); $materno = isset($ap[1]) ? trim($ap[1]) : ''; $nombres = trim($partes[1]);
			} else {
				$paterno = ''; $materno = ''; $nombres = $nombre;
			}
			$completo = $paterno !== '' ? $paterno . '/' . $materno . ',' . $nombres : $nombres;
			$datos = array(
				'NOMBRE_COMPLETO' => $completo, 'APELLIDO_PATERNO' => $paterno, 'APELLIDO_MATERNO' => $materno,
				'NOMBRES' => $nombres, 'NOMBRE_DEPARTAMENTO' => $depto,
			);
			$existe = $this->db->query("SELECT TOP 1 id FROM dbo.empleados WHERE empresa_id = 25 AND NUMERO = ?", array($numero))->row_array();
			if ($existe) {
				$datos['ESTATUS'] = 'A';
				$this->empleadoModel_Eventuales->update_empleado($existe['id'], $datos);
				$actualizados++;
			} else {
				$datos['NUMERO'] = $numero; $datos['NOMBRE_PUESTO'] = '';
				$this->empleadoModel_Eventuales->insert_empleado($datos);
				$altas++;
			}
			$this->empleadoModel_Eventuales->traerChecadas($numero);
		}
		if (!$altas && !$actualizados && !$errores) { $res(array('exito' => false, 'mensaje' => 'El archivo no trae empleados.')); return; }
		$res(array('exito' => true, 'altas' => $altas, 'actualizados' => $actualizados, 'errores' => $errores));
	}


}