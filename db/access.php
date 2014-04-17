<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'local/inscricoes:configure_activity' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

);
