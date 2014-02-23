<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_inscricoes';
$plugin->version = 2014021001;
$plugin->requires  = 2013051400; //Moodle 2.5
$plugin->dependencies = array(
    'local_sccp' => 2013100500,
);
