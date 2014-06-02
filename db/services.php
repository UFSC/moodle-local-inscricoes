<?php

$functions = array(
        'local_inscricoes_subscribe_user' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'subscribe_user',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Subscribe a user into a Moodle category corresponding to a Registration System activity',
                'type'        => 'write',
        ),

        'local_inscricoes_unsubscribe_user' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'unsubscribe_user',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Unsubscribe a user from a Moodle category corresponding to a Registration System activity',
                'type'        => 'write',
        ),

        'local_inscricoes_add_edition' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'add_edition',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Add a new edition to an activity',
                'type'        => 'write',
        ),

        'local_inscricoes_get_user_status' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'get_user_status',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Get a user status related to an activity/edition',
                'type'        => 'read',
        ),
);

$services = array(
        'Sistema de Inscricoes' => array(
                'functions' => array ('local_inscricoes_subscribe_user',
                                      'local_inscricoes_unsubscribe_user',
                                      'local_inscricoes_add_edition',
                                      'local_inscricoes_get_user_status'
                                     ),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
