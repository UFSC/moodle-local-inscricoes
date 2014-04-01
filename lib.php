<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/inscricoes/locallib.php');

function local_inscricoes_extends_settings_navigation(navigation_node $navigation) {
    global $PAGE;

    if (is_a($PAGE->context, 'context_coursecat')) {
        $category_node = $navigation->get('categorysettings');
        $config_activity = has_capability('local/inscricoes:configure_activity' , $PAGE->context);
        $activities = local_inscricoes_get_activities($PAGE->context->instanceid, true);
        $enable = !empty($activities);
        $config_report = $enable && has_capability('local/inscricoes:configure_report' , $PAGE->context);
        $see_progress = $enable && has_capability('local/inscricoes:see_progress' , $PAGE->context);
        $see_completion = $enable && has_capability('local/inscricoes:see_completion' , $PAGE->context);
        // $send_grades = $enable && has_capability('local/inscricoes:send_grades' , $PAGE->context);

        if($config_activity || $config_report || $see_progress || $see_completion || $send_grades) {
            $node = $category_node->add(get_string('menu_title', 'local_inscricoes'), null, navigation_node::TYPE_CONTAINER);
            if($config_activity) {
                $node->add(get_string('pluginname', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/configure_activity.php', array('contextid' => $PAGE->context->id)));
            }
            if($config_report) {
                $node->add(get_string('inscricoes:configure_report', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/configure_report.php', array('contextid' => $PAGE->context->id)));
            }
            if($see_progress) {
                $node->add(get_string('inscricoes:see_progress', 'local_inscricoes'),
                           new moodle_url('/local/inscricoes/reports/progress.php', array('contextid' => $PAGE->context->id)));
            }
            if($see_completion) {
                $node->add(get_string('inscricoes:see_completion', 'local_inscricoes'),
                               new moodle_url('/local/inscricoes/reports/completion.php', array('contextid' => $PAGE->context->id)));
            }
            // if($send_grades) {
            //     $node->add(get_string('inscricoes:send_grades', 'local_inscricoes'),
            //                new moodle_url('/local/inscricoes/see_completion.php', array('contextid' => $PAGE->context->id)));
            // }
        }
    }
}
