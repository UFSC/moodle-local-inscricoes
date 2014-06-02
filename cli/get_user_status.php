<?php

    define('CLI_SCRIPT', true);

    include('../../../config.php');
    include($CFG->libdir . '/filelib.php');

    $address = 'https://mariani.moodle.ufsc.br/unasus-cp';
    $token = '85ce612d4187a855d333664d2c0adb5c';

    // ---------------------------------------------------------
    $function = 'local_inscricoes_get_user_status';

    $params = array();
    $params['idpessoa'] = 100000000759196;
    $params['activityid'] = 4;
    send_rest($address, $token, $function, $params);


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
