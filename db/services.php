<?php

$functions = array(
        'local_inscricoes_subscribe_user' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'subscribe_user',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Subscribe a user into a Moodle category corresponding to a Registration System activity',
                'type'        => 'write',
        ),

        'local_inscricoes_add_edition' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'add_edition',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Add a new edition to an activity',
                'type'        => 'write',
        ),
);

$services = array(
        'Sistema de Inscricoes' => array(
                'functions' => array ('local_inscricoes_subscribe_user',
                                      'local_inscricoes_add_edition'),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
