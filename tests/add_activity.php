<?php

include(dirname(__FILE__). '/config.php');

$function = 'local_inscricoes_add_activity';

$params = array();
$params['idpessoa'] = 100000000319551;
$params['categoryid'] = 57;
$params['activityid'] = 97;
$params['activityname'] = 'Curso de Esperanto';
$params['roles[0]'] = 'student';
$params['roles[1]'] = 'leitor';

send_rest($address, $token, $function, $params);

$params = array();
$params['idpessoa'] = 100000000319551;
$params['categoryid'] = 57;
$params['activityid'] = 98;
$params['activityname'] = 'Curso de Latim';
$params['roles[0]'] = 'student';

send_rest($address, $token, $function, $params);


