<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reporte_model extends CI_Model {

    public function procesar_linea($fila, $numero_linea) {
    
         if ($numero_linea % 2 != 0) {
            $fila = str_replace(",", "", $fila);
        }
        // A todas las líneas les quitamos las comillas
        $fila = str_replace('"', '', $fila);


        return $fila;
    }
}