<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/inscricoes/reports/renderer.php');

$contextid = required_param('contextid', PARAM_INT);
require_login();

$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}
require_capability('local/inscricoes:see_progress', $context);

$group_name = optional_param('group_name', '', PARAM_TEXT);
$days_before = optional_param('days_before', 15, PARAM_INT);
$completed_modules = optional_param('completed_modules', -1, PARAM_INT);
$studentsorderby = optional_param('studentsorderby', 'name', PARAM_ALPHAEXT);
$coursesorderby = optional_param('coursesorderby', 'sortorder', PARAM_ALPHAEXT);

$base_url = new moodle_url("/local/inscricoes/reports/progress.php", array('contextid' => $contextid));

$categoryid = $context->instanceid;
$PAGE->set_category_by_id($categoryid);
$PAGE->set_url($base_url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('coursecategory');
$PAGE->set_title(get_string('pluginname', 'local_inscricoes'));
$PAGE->set_heading(get_string('pluginname', 'local_inscricoes'));

$renderer = $PAGE->get_renderer('local_inscricoes');
$renderer->show_progress_report($context, $group_name, $days_before, $completed_modules, $studentsorderby, $coursesorderby);
