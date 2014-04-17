<?php

    define('CLI_SCRIPT', true);

    include('../../../config.php');
    include($CFG->libdir . '/filelib.php');

    $address = 'https://mariani.moodle.ufsc.br/unasus-cp';
    $token = '661e134668add6e7b2cabec3e6b0efad';

    // ---------------------------------------------------------
    $function = 'local_inscricoes_add_edition';

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 1;
    $params['editionname'] = 'Edição Teste 1';
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['editionname'] = 'Edição Teste 2';
    send_rest($address, $token, $function, $params);

    // ---------------------------------------------------------
    $function = 'local_inscricoes_subscribe_user';

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000220587; //Daniel
    $params['role'] = 'student';
    $params['aditional_fields'] = json_encode(array('cbo'=>'12345', 'atividade'=>'enfermeiro'));
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000375045; //Caio
    $params['role'] = 'student';
    $params['aditional_fields'] = json_encode(array('cbo'=>'23456', 'atividade'=>'engenheiro'));
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000471528; //Francisco
    $params['role'] = 'student';
    $params['aditional_fields'] = json_encode(array('cbo'=>'34567', 'atividade'=>'programador'));
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000329086; //Julião
    $params['role'] = 'student';
    $params['aditional_fields'] = json_encode(array('cbo'=>'34567', 'atividade'=>'programador'));
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000231984; //Zé
    $params['role'] = 'student';
    $params['aditional_fields'] = '';
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000319551; //Mariani
    $params['role'] = 'teacher';
    $params['aditional_fields'] = '';
    send_rest($address, $token, $function, $params);

    $params = array();
    $params['activityid'] = 1;
    $params['editionid'] = 2;
    $params['idpessoa'] = 100000000228517; //Dyck
    $params['role'] = 'teacher';
    $params['aditional_fields'] = '';
    send_rest($address, $token, $function, $params);
    // ---------------------------------------------------------


function send_rest($address, $token, $function, $params) {
    echo "\n----------------------------------------------\n";
    echo "EXECUTANDO: {$function}\n";
    $restformat = 'json';
    $options = array('CURLOPT_SSL_VERIFYHOST' => 1, 'CURLOPT_SSL_VERIFYPEER' => 0);

    $serverurl = $address . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction=' . $function.
                            '&moodlewsrestformat=' . $restformat;

    $curl = new \curl(array('debug' => false));
    $response = $curl->post($serverurl, $params, $options);
    $curlerrno = $curl->get_errno();
    if (!empty($curlerrno)) {
        var_dump($curlerrno, $curl->error); exit;
    }
    $curlinfo = $curl->get_info();
    if ($curlinfo['http_code'] != 200) {
        var_dump($curlinfo); exit;
    }

    var_dump($response);
}
