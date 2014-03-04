<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && isset($ADMIN)) {
    $pagina_settings = new admin_settingpage('inscricoessettings', get_string('pluginname', 'local_inscricoes'));

    $pagina_settings->add(new admin_setting_configtext('local_inscricoes/ws_inscricoes_url',
                            get_string('ws_inscricoes_url', 'local_inscricoes'), '', ''));

    $pagina_settings->add(new admin_setting_configtext('local_inscricoes/ws_inscricoes_login',
                            get_string('ws_inscricoes_login', 'local_inscricoes'), '', ''));

    $pagina_settings->add(new admin_setting_configpasswordunmask('local_inscricoes/ws_inscricoes_password',
                            get_string('ws_inscricoes_password', 'local_inscricoes'), '', ''));

    $ADMIN->add('localplugins', $pagina_settings);
}
