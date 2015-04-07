<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && isset($ADMIN)) {
    $page_settings = new admin_settingpage('inscricoessettings', get_string('pluginname', 'local_inscricoes'));
    $page_settings->add(new admin_setting_heading('local_inscricoes/settings', '', get_string('pluginname_desc', 'local_inscricoes')));

    $allroles = role_get_names(context_system::instance());
    $roles = array();
    foreach($allroles AS $rid=>$r) {
        $roles[$rid] = $r->localname;
    }
    $page_settings->add(new admin_setting_configmultiselect('local_inscricoes/roles', get_string('roles', 'local_inscricoes'),
                    get_string('roles_desc', 'local_inscricoes'), array(3, 5), $roles));

    $ADMIN->add('localplugins', $page_settings);
}
