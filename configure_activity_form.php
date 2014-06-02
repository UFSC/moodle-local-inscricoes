<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class configure_activity_form extends moodleform {

    /**
     * Define the relationshipgroup edit form
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;
        $activity = $this->_customdata['data'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('text', 'externalactivityid', get_string('externalactivityid', 'local_inscricoes'), 'maxlength="10" size="5"');
        $mform->addRule('externalactivityid', get_string('required'), 'required', null, 'client');
        $mform->setType('externalactivityid', PARAM_INT);

        $mform->addElement('selectyesno', 'enable', get_string('enable'));

        $this->add_action_buttons();

        $this->set_data($activity);
    }

    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        if($data['externalactivityid'] < 0) {
            $errors['externalactivityid'] = get_string('externalactivityid_invalid', 'local_inscricoes');
        } else {
            $sql = "SELECT id
                      FROM {inscricoes_activities}
                     WHERE externalactivityid = :externalactivityid
                       AND contextid != :contextid";
            if($DB->record_exists_sql($sql, array('externalactivityid'=>$data['externalactivityid'], 'contextid'=>$data['contextid']))) {
                $errors['externalactivityid'] = get_string('externalactivityid_exists', 'local_inscricoes');
            }
        }

        return $errors;
    }

}

