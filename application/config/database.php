<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;


$db['default'] = array(
	'dsn'	=> '',
    'hostname' => '192.168.128.245\TASQLEXPRESS',//'192.168.128.24',//'192.168.128.24',//
    'username' => 'sa',
    'password' => 'TANet001',//'dbAin1',//'dbAin1',//
    'database' => 'Nominas',
    'dbdriver' => 'sqlsrv',
	'dbprefix' => '',
	'pconnect' => TRUE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt'  => FALSE,
	'autoinit' => TRUE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);
//Configuracion de la otra BD MYSQL
$db['another_db'] = array(
    'dsn'       => '',
    'hostname' => '192.168.128.146',
    'username' => 'biostar2_ac_user',
    'password' => 'admin123',
    'database' => 'biostar2_ac',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => (ENVIRONMENT !== 'production'),
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt'  => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => array(),
    'save_queries' => TRUE
);


$db['another_db']['port'] = 3312;