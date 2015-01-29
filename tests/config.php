<?php

define('CLI_SCRIPT', true);

include(dirname(__FILE__). '/../../../config.php');
include($CFG->libdir . '/filelib.php');

$address = 'https://mariani.moodle.ufsc.br/grupos2';
$token = '7094ccd9a54fedd982704c57f5f6b82e';



function send_rest($address, $token, $function, $params) {
    echo "\n----------------------------------------------\n";
    echo "EXECUTANDO: {$function}\n";
    $restformat = 'json';
    $options = array('CURLOPT_SSL_VERIFYHOST' => 0, 'CURLOPT_SSL_VERIFYPEER' => 0);

    $serverurl = $address . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction=' . $function.
                            '&moodlewsrestformat=' . $restformat;

    $curl = new \curl(array('debug' => false));
    $response = $curl->post($serverurl, $params, $options);
    $curlerrno = $curl->get_errno();
    if (!empty($curlerrno)) {
        echo "\n ----  ERRO ----- \n";
        var_dump($curlerrno, $curl->error);
        exit;
    }

    $curlinfo = $curl->get_info();
    if ($curlinfo['http_code'] != 200) {
        echo "\n ----  HTTP_CODE != 200 ----- \n";
        var_dump($curlinfo);
        exit;
    }

    echo "\n ----  Resposta ----- \n";
    var_dump($response);
}
