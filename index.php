<?php

require('../../config.php');
require_once($CFG->dirroot . '/local/inscricoes/classes/inscricoes.php');

$contextid = required_param('contextid', PARAM_INT);

require_login();
$context = context::instance_by_id($contextid, MUST_EXIST);
if ($context->contextlevel != CONTEXT_COURSECAT) {
    print_error('invalidcontext');
}
require_capability('local/inscricoes:view', $context);

$category = $DB->get_record('course_categories', array('id'=>$context->instanceid), '*', MUST_EXIST);
$returnurl = new moodle_url('/course/index.php', array('categoryid'=>$category->id));
$baseurl = new moodle_url('/local/inscricoes/index.php', array('contextid'=>$contextid));

$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('title', 'local_inscricoes'));
$PAGE->set_heading($COURSE->fullname);

echo $OUTPUT->header();

$activities = $DB->get_records('inscricoes_activities', array('contextid'=>$context->id));
if (empty($activities)) {
    echo $OUTPUT->heading(get_string('no_activities', 'local_inscricoes'));
    echo $OUTPUT->single_button($returnurl, get_string('back'));
    echo $OUTPUT->footer();
    exit;
}

$activityid = optional_param('activityid', 0, PARAM_INT);
if (count($activities) == 1) {
    $activity = reset($activities);
    $activityid = $activity->id;
} else {
    if (isset($activities[$activityid])) {
        $activity = $activities[$activityid];
    } else {
        $activityid = 0;
        $activity = false;
    }
}

$activities_menu = array();
foreach ($activities AS $act) {
    $activities_menu[$act->id] = $act->externalactivityname;
}

echo $OUTPUT->heading(get_string('title', 'local_inscricoes') . ': ' . $category->name);

$select = new single_select($baseurl, 'activityid', $activities_menu, $activityid);
$select->label = get_string('activity', 'local_inscricoes');
$select->formid = 'chooseactivity';
echo html_writer::tag('div', $OUTPUT->render($select), array('id'=>'activity_selector'));

if ($activity) {
    $additional_fields = local_inscricoes::get_additional_fields($activity->id);

    $users = local_inscricoes::get_users($activity->id);
    $values = local_inscricoes::get_additional_field_values($activity->id);

    $allroles = role_get_names($context);

    $data = array();
    $count = 0;
    foreach ($users AS $u) {
        $count++;
        $line = array($count, $allroles[$u->roleid]->localname, fullname($u));
        if ($additional_fields) {
            foreach ($additional_fields AS $field) {
                if (isset($values[$u->userid][$field])) {
                    $v =  $values[$u->userid][$field];
                    $line[] = "{$v[1]} ({$v[0]})";
                } else {
                    $line[] = '';
                }
            }
        }
        $data[] = $line;
    }

    $table = new html_table();
    $table->head = array('', get_string('role'), get_string('fullname'));
    $table->align = array('center', 'left', 'left');
    $table->colclasses = array('centeralign', 'leftalign', 'leftalign');

    foreach ($additional_fields AS $field) {
        $table->head[] = $field;
        $table->align[] = 'left';
        $table->colclasses[] = 'leftalign';
    }

    $table->id = 'users';
    $table->data = $data;

    echo html_writer::tag('div', html_writer::table($table), array('id'=>'activity_users'));
}

echo $OUTPUT->footer();
