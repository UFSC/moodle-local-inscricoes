<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/inscricoes/reports/renderer.php');

$contextid = required_param('contextid', PARAM_INT);
require_login();

$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}
require_capability('local/inscricoes:see_completion', $context);

$csv = optional_param('csv', false, PARAM_ALPHAEXT);

$base_url = new moodle_url("/local/inscricoes/reports/completion.php", array('contextid' => $contextid));

$categoryid = $context->instanceid;
$PAGE->set_category_by_id($categoryid);
$PAGE->set_url($base_url);
$PAGE->set_context($context);
$PAGE->set_pagelayout('coursecategory');
$PAGE->set_title(get_string('pluginname', 'local_inscricoes'));
$PAGE->set_heading(get_string('pluginname', 'local_inscricoes'));

$renderer = $PAGE->get_renderer('local_inscricoes');
$renderer->show_completion_report($context, $csv);
