<?php

include(dirname(__FILE__). '/config.php');

$function = 'local_inscricoes_get_roles';

$params = array();

send_rest($address, $token, $function, $params);
