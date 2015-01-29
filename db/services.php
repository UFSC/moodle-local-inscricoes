<?php

$functions = array(
        'local_inscricoes_get_categories' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'get_categories',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Retorna as categorias às quais o usuário pode associar a uma atividade no Sistema de Inscrições',
                'type'        => 'read',
        ),

        'local_inscricoes_get_roles' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'get_roles',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Retorna os papeis que podem ser atribuídos a pessoas via Sistema de Inscrições',
                'type'        => 'read',
        ),

        'local_inscricoes_add_activity' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'add_activity',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Add a new activity',
                'type'        => 'write',
        ),

        'local_inscricoes_subscribe_user' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'subscribe_user',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Subscribe a user into a cohort corresponding to an activity and role',
                'type'        => 'write',
        ),

        'local_inscricoes_unsubscribe_user' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'unsubscribe_user',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Unsubscribe a user from all the cohorts corresponding to an activity',
                'type'        => 'write',
        ),

        'local_inscricoes_get_user_activities' => array(
                'classname'   => 'local_inscricoes_external',
                'methodname'  => 'get_user_activities',
                'classpath'   => 'local/inscricoes/externallib.php',
                'description' => 'Get a list of activities where the user is subscribed',
                'type'        => 'read',
        ),
);

$services = array(
        'Sistema de Inscricoes' => array(
                'functions' => array ('local_inscricoes_get_categories',
                                      'local_inscricoes_get_roles',
                                      'local_inscricoes_add_activity',
                                      'local_inscricoes_subscribe_user',
                                      'local_inscricoes_unsubscribe_user',
                                      'local_inscricoes_get_user_activities'
                                     ),
                'restrictedusers' => 1,
                'enabled'=>1,
        )
);
