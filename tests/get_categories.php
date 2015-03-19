
<?php

include(dirname(__FILE__). '/config.php');

$function = 'local_inscricoes_get_categories';

$params = array();
$params['idpessoa'] = 100000000319551;

send_rest($address, $token, $function, $params);
