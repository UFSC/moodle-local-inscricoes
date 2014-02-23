<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/inscricoes/locallib.php');

function local_inscricoes_extends_settings_navigation(navigation_node $navigation) {
    global $PAGE;

    if (is_a($PAGE->context, 'context_coursecat')) {
        $category_node = $navigation->get('categorysettings');
        $manage = has_capability('local/inscricoes:manage' , $PAGE->context);
        if($manage) {
            $category_node->add(get_string('pluginname', 'local_inscricoes'),
                       new moodle_url('/local/inscricoes/manage.php', array('contextid' => $PAGE->context->id)));
        }

        $regs = local_inscricoes_get_config_activities($PAGE->context->instanceid, true);
        $enable = !empty($regs);
        $config = $enable && has_capability('local/inscricoes:config' , $PAGE->context);
        $see_progress = $enable && has_capability('local/inscricoes:see_progress' , $PAGE->context);
        $see_completion = $enable && has_capability('local/inscricoes:see_completion' , $PAGE->context);
        $send_grades = $enable && has_capability('local/inscricoes:send_grades' , $PAGE->context);

        if($config || $see_progress || $see_completion || $send_grades) {
            $node = $category_node->add(get_string('menu_title', 'local_inscricoes'), null, navigation_node::TYPE_CONTAINER);
            if($config) {
                $node->add(get_string('inscricoes:config', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/configure.php', array('contextid' => $PAGE->context->id)));
            }
            if($see_progress) {
                $node->add(get_string('inscricoes:see_progress', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/see_progress.php', array('contextid' => $PAGE->context->id)));
            }
            if($see_completion) {
                $node->add(get_string('inscricoes:see_completion', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/see_completion.php', array('contextid' => $PAGE->context->id)));
            }
            if($send_grades) {
                $node->add(get_string('inscricoes:send_grades', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/see_completion.php', array('contextid' => $PAGE->context->id)));
            }
        }
    }
}
