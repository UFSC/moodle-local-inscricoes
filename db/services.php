<?php

$functions = array(
        'local_inscricoes_subscribe_user' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'subscribe_user',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Subscribe a user into a Moodle category corresponding to a Registration System activity',
                'type'        => 'write',
        ),
);

$services = array(
        'Sistema de Inscricoes' => array(
                'functions' => array ('local_inscricoes_subscribe_user'),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
