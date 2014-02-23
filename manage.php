<?php

require('../../config.php');
require_once($CFG->dirroot.'/local/inscricoes/locallib.php');
require_once($CFG->dirroot.'/local/inscricoes/manage_form.php');
require_once($CFG->libdir.'/adminlib.php');

$contextid = required_param('contextid', PARAM_INT);
$configactivityid = optional_param('configactivityid', 0, PARAM_INT);

require_login();
$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}
require_capability('local/inscricoes:manage', $context);

$category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
$returnurl = new moodle_url('/course/index.php', array('categoryid'=>$category->id));
$baseurl = new moodle_url('/local/inscricoes/manage.php', array('contextid'=>$contextid));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('inscricoes:config', 'local_inscricoes'));
$PAGE->set_heading($COURSE->fullname);

$regs = local_inscricoes_get_config_activities($category->id, false, true);
if(empty($regs)) {
    $registration = new stdclass();
    $registration->id = 0;
    $registration->contextid = $contextid;
    $registration->activityid = 0;
    $registration->studentroleid = 0;
    $registration->createcohortbyedition = 0;
    $registration->enable = 0;
} else {
    $count = 0;
    foreach($regs AS $reg) {
        if($reg->contextid == $contextid) {
            $count++;
        }
    }
    if($count == 0) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('inscricoes:manage', 'local_inscricoes') . ': ' . $category->name);
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        echo get_string('already_have_registration', 'local_inscricoes');
        echo html_writer::start_tag('UL');
        foreach($regs AS $reg) {
            $contextcat = context::instance_by_id($reg->contextid, MUST_EXIST);
            $category = $DB->get_record('course_categories', array('id'=>$contextcat->instanceid));
            $url = new moodle_url('/local/inscricoes/manage.php', array('contextid'=>$reg->contextid));
            echo html_writer::tag('LI', html_writer::link($url, $category->name));
        }
        echo html_writer::end_tag('UL');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    } else if($count < count($regs)) {
        print_error('inconsistency', 'local_inscricoes', $category->name);
    } else if($count > 1) {
        print_error('TODO: ESTA VERSÃO NÃO SUPORTE MAIS DE UMA INSCRIÇÃO NA MESMA CATEGORIA', 'local_inscricoes');
    }
    $registration = reset($regs);
}

$editform = new manage_form(null, array('data'=>$registration));

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    if ($data->id) {
        $DB->update_record('inscricoes_config_activities', $data);
    } else {
        $data->timecreated = time();
        $DB->insert_record('inscricoes_config_activities', $data);
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inscricoes:manage', 'local_inscricoes') . ': ' . $category->name);
echo $editform->display();
echo $OUTPUT->footer();
