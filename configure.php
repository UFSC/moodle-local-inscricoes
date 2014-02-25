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
require_capability('local/inscricoes:config', $context);

$regs = local_inscricoes_get_config_activities($context->instanceid, true);
if(empty($regs)) {
    print_error('not_registration_enable' ,'local_inscricoes');
}

$category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
$returnurl = new moodle_url('/course/index.php', array('categoryid'=>$category->id));
$baseurl = new moodle_url('/local/inscricoes/configure.php', array('contextid'=>$contextid));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('inscricoes:config', 'local_inscricoes'));
$PAGE->set_heading($COURSE->fullname);

$configs = local_inscricoes_get_config_reports($context->instanceid, true);
if(empty($configs)) {
    $config = new stdclass();
    $config->minoptionalcourses = 0;
    $config->maxoptionalcourses = 0;
    $config->optionalatonetime = 0;
} else {
    $count = 0;
    foreach($configs AS $cf) {
        if($cf->contextid == $contextid) {
            $count++;
        }
    }
    if($count == 0) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('inscricoes:config', 'local_inscricoes') . ': ' . $category->name);
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
        echo get_string('already_have_report', 'local_inscricoes');
        echo html_writer::start_tag('UL');
        foreach($configs AS $cf) {
            $contextcat = context::instance_by_id($cf->contextid, MUST_EXIST);
            $category = $DB->get_record('course_categories', array('id'=>$contextcat->instanceid));
            $url = new moodle_url('/local/inscricoes/configure.php', array('contextid'=>$cf->contextid));
            echo html_writer::tag('LI', html_writer::link($url, $category->name));
        }
        echo html_writer::end_tag('UL');
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    } else if($count < count($regs)) {
        print_error('inconsistency_report', 'local_inscricoes', $category->name);
    } else if($count > 1) {
        print_error('TODO: ESTA VERSÃO NÃO SUPORTA MAIS DE UM RELATÓRIO NA MESMA CATEGORIA', 'local_inscricoes');
    }
    $config = reset($configs);
}

$courses = local_inscricoes_get_courses($contextid, false);

/// Process any form submission.
if (optional_param('savechanges', false, PARAM_BOOL) && confirm_sesskey()) {
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
    foreach($courses as $c) {
        if(isset($types[$c->courseid])) {
            $rec = new stdclass();
            $rec->type = $types[$c->courseid];
            $rec->workload = $workloads[$c->courseid];
            $rec->coursedependencyid = $dependencies[$c->courseid];
            $rec->inscribestartdate = make_timestamp($startyears[$c->courseid], $startmonths[$c->courseid], $startdays[$c->courseid]);
            $rec->inscribeenddate = make_timestamp($endyears[$c->courseid], $endmonths[$c->courseid], $enddays[$c->courseid]);
            $rec->timemodified = time();
            if(empty($c->icid)) {
                $rec->contextid = $contextid;
                $rec->courseid = $c->courseid;
                $DB->insert_record('inscricoes_courses', $rec);
            } else {
                $rec->id = $c->icid;
                $DB->update_record('inscricoes_courses', $rec);
            }
            $courseids[] = $c->courseid;
        }
    }

    if(empty($courseids)) {
        $DB->delete_records('inscricoes_courses', array('contextid'=>$contextid));
    } else {
        list($whereroles, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid', false);
        $sql = "contextid = :contextid AND courseid {$whereroles}";
        $params['contextid'] = $contextid;
        $DB->delete_records_select('inscricoes_courses', $sql, $params);
    }

    $config->minoptionalcourses = optional_param('minoptionalcourses', -1, PARAM_INT);
    $config->maxoptionalcourses = optional_param('maxoptionalcourses', -1, PARAM_INT);
    $config->optionalatonetime = optional_param('optionalatonetime', 0, PARAM_INT);
    $config->studentroleid = optional_param('studentroleid', -1, PARAM_INT);

    if($config->minoptionalcourses >= 0) {
        $config->timemodified = time();
        if(isset($config->id)) {
            $DB->update_record('inscricoes_config_reports', $config);
        } else {
            $config->contextid = $contextid;
            $DB->insert_record('inscricoes_config_reports', $config);
        }
    }

    redirect($baseurl, get_string('changessaved'), 1);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('inscricoes:config', 'local_inscricoes') . ': ' . $category->name);

echo html_writer::start_tag('form', array('action'=>$baseurl->out_omit_querystring(), 'method'=>'post'));
echo html_writer::start_tag('div');
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
echo html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'contextid', 'value'=>$contextid));

$type_options = array(0=>get_string('not_classified', 'local_inscricoes'),
                      1=>get_string('mandatory', 'local_inscricoes'),
                      2=>get_string('optional', 'local_inscricoes'),
                      3=>get_string('ignore', 'local_inscricoes'));

$data = array();
foreach($courses as $c) {
    $tab = new html_table();
    $tab->data = array();

    $tab->data[] = array(get_string('type', 'local_inscricoes'),
                         html_writer::select($type_options, "type[$c->courseid]", $c->type, false));
    $tab->data[] = array(get_string('workload', 'local_inscricoes'),
                         html_writer::empty_tag('input', array('type'=>'text', 'name'=>"workload[$c->courseid]", 'value'=>$c->workload, 'size'=>5)));

    $startdate = get_string('from') . ': '
                 . html_writer::select_time('days', "startdays[$c->courseid]", $c->inscribestartdate)
                 . html_writer::select_time('months', "startmonths[$c->courseid]", $c->inscribestartdate)
                 . html_writer::select_time('years', "startyears[$c->courseid]", $c->inscribestartdate);
    $enddate   = get_string('to') . ': '
                 . html_writer::select_time('days', "enddays[$c->courseid]", $c->inscribeenddate)
                 . html_writer::select_time('months', "endmonths[$c->courseid]", $c->inscribeenddate)
                 . html_writer::select_time('years', "endyears[$c->courseid]", $c->inscribeenddate);
    $tab->data[] = array(get_string('inscribeperiodo', 'local_inscricoes'),
                   $startdate . '   ' . $enddate);

    $course_options = array('0' => get_string('none'));
    foreach($courses as $copt) {
        if($copt->courseid != $c->courseid) {
            $course_options[$copt->courseid] = $copt->fullname;
        }
    }

    $tab->data[] = array(get_string('dependency', 'local_inscricoes'),
                         html_writer::select($course_options, "dependencies[$c->courseid]", $c->coursedependencyid, false));

    $curl = new moodle_url('/course/view.php', array('id'=>$c->courseid));
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
echo html_writer::select($options_opt, "minoptionalcourses", $config->minoptionalcourses, false);

echo html_writer::empty_tag('br');
echo html_writer::tag('B', get_string('maxoptionalcourses', 'local_inscricoes'));
echo html_writer::select($options_opt, "maxoptionalcourses", $config->maxoptionalcourses, false);

echo html_writer::empty_tag('br');
$yesnooptions = array('1'=>get_string('yes'), '0'=>get_string('no'));
echo html_writer::tag('B', get_string('optionalatonetime', 'local_inscricoes'));
echo html_writer::select($yesnooptions, "optionalatonetime", $config->optionalatonetime, false);

echo html_writer::end_tag('div');

echo html_writer::start_tag('div', array('class'=>'buttons'));
echo html_writer::empty_tag('input', array('type'=>'submit', 'name'=>'savechanges', 'value'=>get_string('savechanges')));
echo html_writer::link($returnurl, '  ' . get_string('cancel'));
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
