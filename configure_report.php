<?php

require('../../config.php');
require_once($CFG->dirroot.'/local/inscricoes/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

$contextid = required_param('contextid', PARAM_INT);
require_login();
$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}
require_capability('local/inscricoes:configure_report', $context);

$activities = local_inscricoes_get_activities($context->instanceid, true);
if(empty($activities)) {
    print_error('not_activity_enable' ,'local_inscricoes');
}
$activity = reset($activities);

$category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
$returnurl = new moodle_url('/course/index.php', array('categoryid'=>$category->id));
$baseurl = new moodle_url('/local/inscricoes/configure_report.php', array('contextid'=>$contextid));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('inscricoes:configure_report', 'local_inscricoes'));
$PAGE->set_heading($COURSE->fullname);

$reports = local_inscricoes_get_reports($context->instanceid, true);
if(empty($reports)) {
    $report = new stdclass();
    $report->activityid = $activity->id;
    $report->contextid = $contextid;
    $report->minoptionalcourses = 0;
    $report->maxoptionalcourses = 0;
    $report->optionalatonetime = 0;
} else {
    $count = 0;
    foreach($reports AS $rep) {
        if($rep->contextid == $contextid) {
            $count++;
        }
    }
    if($count == 0) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('inscricoes:configure_report', 'local_inscricoes') . ': ' . $category->name);
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        echo get_string('already_have_report', 'local_inscricoes');
        echo html_writer::start_tag('UL');
        foreach($reports AS $cf) {
            $contextcat = context::instance_by_id($cf->contextid, MUST_EXIST);
            $category = $DB->get_record('course_categories', array('id'=>$contextcat->instanceid));
            $url = new moodle_url('/local/inscricoes/configure_report.php', array('contextid'=>$cf->contextid));
            echo html_writer::tag('LI', html_writer::link($url, $category->name));
        }
        echo html_writer::end_tag('UL');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    } else if($count < count($reports)) {
        print_error('inconsistency_report', 'local_inscricoes', $category->name);
    } else if($count > 1) {
        print_error('TODO: ESTA VERSÃO NÃO SUPORTA MAIS DE UM RELATÓRIO NA MESMA CATEGORIA', 'local_inscricoes');
    }
    $report = reset($reports);
}

$courses = local_inscricoes_get_potential_courses($contextid, $category->path);

/// Process any form submission.
if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {

    $report->minoptionalcourses = required_param('minoptionalcourses', PARAM_INT);
    $report->maxoptionalcourses = required_param('maxoptionalcourses', PARAM_INT);
    $report->optionalatonetime = required_param('optionalatonetime', PARAM_INT);
    $report->activityid = required_param('activityid', PARAM_INT);
    $report->contextid = required_param('contextid', PARAM_INT);

    $report->timemodified = time();
    if(isset($report->id)) {
        $DB->update_record('inscricoes_reports', $report);
    } else {
        $report->contextid = $contextid;
        $report->id = $DB->insert_record('inscricoes_reports', $report);
    }

    $types = optional_param_array('type', array(), PARAM_INT);
    $workloads = optional_param_array('workload', array(), PARAM_INT);
    $dependencies = optional_param_array('dependencies', array(), PARAM_INT);

    $startdays = optional_param_array('startdays', array(), PARAM_INT);
    $startmonths = optional_param_array('startmonths', array(), PARAM_INT);
    $startyears = optional_param_array('startyears', array(), PARAM_INT);

    $enddays = optional_param_array('enddays', array(), PARAM_INT);
    $endmonths = optional_param_array('endmonths', array(), PARAM_INT);
    $endyears = optional_param_array('endyears', array(), PARAM_INT);

    $courseids = array();
    $errors = array();
    foreach($courses as $c) {
        if(isset($types[$c->id])) {
            $course_report = new stdclass();
            $course_report->type = $types[$c->id];
            $course_report->workload = $workloads[$c->id];
            if($course_report->workload < 0 || $course_report->workload > 360) {
                $errors[$c->fullname][] = get_string('invalid_workload', 'local_inscricoes');
            }
            $course_report->coursedependencyid = $dependencies[$c->id];
            if($dependencies[$c->id] > 0 && !in_array($types[$dependencies[$c->id]], array(1,2))) {
                $errors[$c->fullname][] = get_string('dependecy_not_opt_dem', 'local_inscricoes');
            }
            $course_report->inscribestartdate = make_timestamp($startyears[$c->id], $startmonths[$c->id], $startdays[$c->id]);
            $course_report->inscribeenddate = make_timestamp($endyears[$c->id], $endmonths[$c->id], $enddays[$c->id]);
            if($course_report->inscribeenddate < $course_report->inscribestartdate) {
                $errors[$c->fullname][] = get_string('end_before_start', 'local_inscricoes');
            }
            $course_report->timemodified = time();
            if(empty($c->icid)) {
                $course_report->reportid = $report->id;
                $course_report->courseid = $c->id;
                $DB->insert_record('inscricoes_courses', $course_report);
            } else {
                $course_report->id = $c->icid;
                $DB->update_record('inscricoes_courses', $course_report);
            }
            $courseids[] = $c->id;
        }
    }
    if(!empty($errors)) {
        $SESSION->errors = $errors;
    }

    if(empty($courseids)) {
        $DB->delete_records('inscricoes_courses', array('contextid'=>$contextid));
    } else {
        list($whereroles, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', false);
        $sql = "reportid = :reportid AND courseid {$whereroles}";
        $params['reportid'] = $report->id;
        $DB->delete_records_select('inscricoes_courses', $sql, $params);
    }

    redirect($baseurl, get_string('changessaved'), 1);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inscricoes:configure_report', 'local_inscricoes') . ': ' . $category->name);
echo html_writer::start_tag('DIV', array('class'=>'local_inscricoes'));

if(isset($SESSION->errors)) {
    $errors = $SESSION->errors;
    unset($SESSION->errors);

    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide msg_error');
    echo $OUTPUT->heading(get_string('errors', 'local_inscricoes'));
    echo html_writer::start_tag('UL');
    foreach($errors AS $fullname=>$errs) {
        echo html_writer::tag('LI', $fullname);
        echo html_writer::start_tag('UL');
        foreach($errs AS $err) {
            echo html_writer::tag('LI', $err);
        }
        echo html_writer::end_tag('UL');
    }
    echo html_writer::end_tag('UL');
    echo $OUTPUT->box_end();
}

echo html_writer::start_tag('form', array('action'=>$baseurl->out_omit_querystring(), 'method'=>'post'));
echo html_writer::start_tag('div');
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'activityid', 'value'=>$activity->id));

$type_options = array(0=>get_string('not_classified', 'local_inscricoes'),
                      1=>get_string('mandatory', 'local_inscricoes'),
                      2=>get_string('optional', 'local_inscricoes'),
                      3=>get_string('ignore', 'local_inscricoes'));

$data = array();
foreach($courses as $c) {
    $tab = new html_table();
    $tab->data = array();

    $tab->data[] = array(get_string('type', 'local_inscricoes'),
                         html_writer::select($type_options, "type[$c->id]", $c->type, false));
    $tab->data[] = array(get_string('workload', 'local_inscricoes'),
                         html_writer::empty_tag('input', array('type'=>'text', 'name'=>"workload[$c->id]", 'value'=>$c->workload, 'size'=>5)));

    $startdate = get_string('from') . ': '
                 . html_writer::select_time('days', "startdays[$c->id]", $c->inscribestartdate)
                 . html_writer::select_time('months', "startmonths[$c->id]", $c->inscribestartdate)
                 . html_writer::select_time('years', "startyears[$c->id]", $c->inscribestartdate);
    $enddate   = get_string('to') . ': '
                 . html_writer::select_time('days', "enddays[$c->id]", $c->inscribeenddate)
                 . html_writer::select_time('months', "endmonths[$c->id]", $c->inscribeenddate)
                 . html_writer::select_time('years', "endyears[$c->id]", $c->inscribeenddate);
    $tab->data[] = array(get_string('inscribeperiodo', 'local_inscricoes'),
                   $startdate . '   ' . $enddate);

    $course_options = array('0' => get_string('none'));
    foreach($courses as $copt) {
        if($copt->id != $c->id) {
            $course_options[$copt->id] = $copt->fullname;
        }
    }

    $tab->data[] = array(get_string('dependency', 'local_inscricoes'),
                         html_writer::select($course_options, "dependencies[$c->id]", $c->coursedependencyid, false));

    $curl = new moodle_url('/course/view.php', array('id'=>$c->id));
    $data[] = array(html_writer::link($curl, format_string($c->fullname), array('target'=>'_new')),
                    html_writer::table($tab));
}
$table = new html_table();
$table->head  = array(get_string('coursename', 'local_inscricoes'),
                      get_string('configurations', 'local_inscricoes'));
$table->colclasses = array('leftalign', 'leftalign');
$table->id = 'inscricoes';
$table->attributes['class'] = 'admintable generaltable';
$table->data = $data;
echo html_writer::table($table);

$options_opt = array();
for($i=0; $i <= count($courses); $i++) {
    $options_opt[$i] = $i;
}

echo html_writer::start_tag('div');
echo html_writer::tag('B', get_string('minoptionalcourses', 'local_inscricoes'));
echo html_writer::select($options_opt, "minoptionalcourses", $report->minoptionalcourses, false);

echo html_writer::empty_tag('br');
echo html_writer::tag('B', get_string('maxoptionalcourses', 'local_inscricoes'));
echo html_writer::select($options_opt, "maxoptionalcourses", $report->maxoptionalcourses, false);

echo html_writer::empty_tag('br');
$yesnooptions = array('1'=>get_string('yes'), '0'=>get_string('no'));
echo html_writer::tag('B', get_string('optionalatonetime', 'local_inscricoes'));
echo html_writer::select($yesnooptions, "optionalatonetime", $report->optionalatonetime, false);

echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class'=>'buttons'));
echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'savechanges', 'value'=>get_string('savechanges')));
echo html_writer::link($returnurl, '  ' . get_string('cancel'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

echo html_writer::end_tag('DIV');
echo $OUTPUT->footer();
