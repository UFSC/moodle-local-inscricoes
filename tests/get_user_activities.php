<?php

include(dirname(__FILE__). '/config.php');

$function = 'local_inscricoes_get_user_activities';

$params = array();
$params['idpessoa'] = 100000000228517; // Dyck
// $params['idpessoa'] = 100000000220587; // Daniel

send_rest($address, $token, $function, $params);
