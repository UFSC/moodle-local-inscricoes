<?php

include(dirname(__FILE__). '/config.php');

$function = 'local_inscricoes_subscribe_user';

$params = array();
$params['activityid'] = 97;
$params['idpessoa'] = 100000000228517; // Dyck
$params['role'] = 'student';
$params['aditional_fields'] = json_encode(array( array('cbo', '23456', 'Engenheiro'), array('atividade', 'At.Engenheiro')));
send_rest($address, $token, $function, $params);

$params = array();
$params['activityid'] = 97;
$params['idpessoa'] = 100000000220587; // Daniel
$params['role'] = 'student';
$params['aditional_fields'] = json_encode(array( array('cbo', '12345', 'Enfermeiro'), array('atividade', 'At.Enfermeiro')));
send_rest($address, $token, $function, $params);

$params = array();
$params['activityid'] = 97;
$params['idpessoa'] = 100000000329086; //Julião
$params['role'] = 'student';
$params['aditional_fields'] = json_encode(array( array('cbo', '34567', 'Programador'), array('atividade', 'At.Programador')));
send_rest($address, $token, $function, $params);

$params = array();
$params['activityid'] = 97;
$params['idpessoa'] = 100000000375045; //Caio
$params['role'] = 'student';
$params['aditional_fields'] = json_encode(array( array('cbo', '23456', 'Engenheiro'), array('atividade', 'At.Engenheiro')));
send_rest($address, $token, $function, $params);

// -------------------------------

$params = array();
$params['activityid'] = 98;
$params['idpessoa'] = 100000000329086; //Julião
$params['role'] = 'student';
$params['aditional_fields'] = '';
send_rest($address, $token, $function, $params);

$params = array();
$params['activityid'] = 98;
$params['idpessoa'] = 100000000375045; //Caio
$params['role'] = 'student';
$params['aditional_fields'] = '';
send_rest($address, $token, $function, $params);
