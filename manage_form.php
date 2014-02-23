<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class manage_form extends moodleform {

    /**
     * Define the relationshipgroup edit form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $registration = $this->_customdata['data'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('text', 'activityid', get_string('activityid', 'local_inscricoes'), 'maxlength="10" size="5"');
        $mform->addRule('activityid', get_string('required'), 'required', null, 'client');
        $mform->setType('activityid', PARAM_INT);

        $options_role = array();
        $gradebookroles = explode(',', $CFG->gradebookroles);
        foreach($gradebookroles AS $rid) {
            $options_role[$rid] = $DB->get_field('role', 'shortname', array('id'=>$rid));
        }
        $mform->addElement('select', 'studentroleid', get_string('studentrole', 'local_inscricoes'), $options_role);

        $mform->addElement('selectyesno', 'createcohortbyedition', get_string('createcohortbyedition', 'local_inscricoes'));
        $mform->addElement('selectyesno', 'enable', get_string('enable'));

        $this->add_action_buttons();

        $this->set_data($registration);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if(empty($data['activityid']) || $data['activityid'] < 0) {
            $errors['activityid'] = get_string('activityid_invalid', 'local_inscricoes');
        } else {
            $sql = "SELECT id
                      FROM {inscricoes_config_activities}
                     WHERE activityid = :activityid
                       AND contextid != :contextid";
            if($DB->record_exists_sql($sql, array('activityid'=>$data['activityid'], 'contextid'=>$data['contextid']))) {
                $errors['activityid'] = get_string('activityid_exists', 'local_inscricoes');
            }
        }

        return $errors;
    }

}

