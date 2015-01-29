<?php

include(dirname(__FILE__). '/config.php');

$function = 'local_inscricoes_unsubscribe_user';

$params = array();
$params['activityid'] = 97;
$params['idpessoa'] = 100000000220587; // Daniel

send_rest($address, $token, $function, $params);
