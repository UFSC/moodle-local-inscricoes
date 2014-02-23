<?php

    require_once('./curl.php');

    $address = 'https://mariani.moodle.ufsc.br/moodle-26';
    $token = '10ab98afef37249f60f47a8843fc52a2';
    $function = 'local_inscricoes_subscribe_user';
    $restformat = 'json';

    $params = new stdClass();
    $params->activityid = 1;
    $params->idpessoa = 100000000319551;
    $params->role = 'student';
    $params->edition = 'Primeira Edição';
    $params->aditional_fields = json_encode(array('cbo'=>'12345', 'atividade'=>'enfermeiro'));

    // header('Content-Type: text/plain');
    $serverurl = $address . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$function;
    $curl = new curl;
    $restformat = ($restformat == 'json')?'&moodlewsrestformat=' . $restformat:'';
    $resp = $curl->post($serverurl . $restformat, $params);
    echo "\n";
    print_r($resp);
    echo "\n";

    $params->activityid = 3;
    $resp = $curl->post($serverurl . $restformat, $params);
    echo "\n";
    print_r($resp);
    echo "\n";
