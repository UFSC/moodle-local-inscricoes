<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_inscricoes';
$plugin->version   = 2014041700;
$plugin->requires  = 2013111800; //Moodle 2.6.1
$plugin->dependencies = array(
    'local_sccp' => 2013100500,
);
