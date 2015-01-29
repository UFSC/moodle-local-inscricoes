<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'local/inscricoes:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

    'local/inscricoes:manage' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'manager' => CAP_ALLOW
        )
    ),

);
