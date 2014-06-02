<?php

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");
require_once("{$CFG->dirroot}/local/inscricoes/locallib.php");

class local_inscricoes_external extends external_api {

    public static function subscribe_user_parameters() {
        return new external_function_parameters (
                    array('activityid' => new external_value(PARAM_INT, 'Activity Id'),
                          'editionid' => new external_value(PARAM_INT, 'Edition id'),
                          'idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP'),
                          'role' => new external_value(PARAM_TEXT, 'Subscription role'),
                          'aditional_fields' => new external_value(PARAM_TEXT, 'Aditional fields (JSON - [name:key:value]*')));
    }

    public static function subscribe_user($activityid, $editionid, $idpessoa, $role_shortname, $aditional_fields) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::subscribe_user_parameters(),
                        array('activityid'=>$activityid, 'editionid'=>$editionid, 'idpessoa'=>$idpessoa,
                              'role'=>$role_shortname, 'aditional_fields'=>$aditional_fields));

        if(empty($role_shortname)) {
            return get_string('empty_role_edition', 'local_inscricoes');
        }

        if(!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_not_configured', 'local_inscricoes');
        }
        if(!$activity->enable) {
            return get_string('activity_not_enable', 'local_inscricoes');
        }

        if(!$edition = $DB->get_record('inscricoes_editions', array('activityid'=>$activity->id, 'externaleditionid'=>$editionid))) {
            return get_string('edition_unknown', 'local_inscricoes');
        }

        try {
            $context = context::instance_by_id($activity->contextid);
        } catch (Exception $e) {
            return get_string('category_unknown', 'local_inscricoes');
        }

        if(!$role = $DB->get_record('role', array('shortname'=>$role_shortname))) {
            return get_string('role_unknown', 'local_inscricoes');
        }
        $invalid_roles = array('manager', 'guest', 'user');
        if(in_array($role_shortname, $invalid_roles)) {
            return get_string('role_invalid', 'local_inscricoes');
        }

        try {
            $user = local_inscricoes_add_user($idpessoa);
            if ($context->contextlevel == CONTEXT_COURSECAT) {
                local_inscricoes_add_cohort_member($context->id, $user->id, $role, $edition);
            } else {
                return get_string('not_coursecat_context', 'local_inscricoes');
            }
            local_inscricoes_add_aditional_fields($activity->id, $user->id, $aditional_fields);
        } catch (dml_write_exception $e) {
            return '40-' . $e->getMessage() . ' - ' . $e->debuginfo;
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return get_string('ok', 'local_inscricoes');
    }

    public static function subscribe_user_returns() {
        return new external_value(PARAM_TEXT, get_string('answer_text', 'local_inscricoes'));
    }

    // ---------------------------------------------------------------------------------------------
    public static function unsubscribe_user_parameters() {
        return new external_function_parameters (
                    array('activityid' => new external_value(PARAM_INT, 'Activity Id'),
                          'editionid' => new external_value(PARAM_INT, 'Edition id'),
                          'idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP'),
                          'role' => new external_value(PARAM_TEXT, 'Subscription role')));
    }

    public static function unsubscribe_user($activityid, $editionid, $idpessoa, $role_shortname) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::unsubscribe_user_parameters(),
                        array('activityid'=>$activityid, 'editionid'=>$editionid, 'idpessoa'=>$idpessoa, 'role'=>$role_shortname));

        if(empty($role_shortname)) {
            return get_string('empty_role_edition', 'local_inscricoes');
        }

        if(!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_not_configured', 'local_inscricoes');
        }
        if(!$activity->enable) {
            return get_string('activity_not_enable', 'local_inscricoes');
        }

        if(!$edition = $DB->get_record('inscricoes_editions', array('activityid'=>$activity->id, 'externaleditionid'=>$editionid))) {
            return get_string('edition_unknown', 'local_inscricoes');
        }

        try {
            $context = context::instance_by_id($activity->contextid);
        } catch (Exception $e) {
            return get_string('category_unknown', 'local_inscricoes');
        }

        if(!$role = $DB->get_record('role', array('shortname'=>$role_shortname))) {
            return get_string('role_unknown', 'local_inscricoes');
        }

        if(!$user = $DB->get_record('user', array('idnumber'=>$idpessoa))) {
            return get_string('idpessoa_unknown', 'local_inscricoes');
        }
        try {
            if ($context->contextlevel == CONTEXT_COURSECAT) {
                local_inscricoes_remove_cohort_member($activity->id, $context->id, $user->id, $role, $edition);
            } else {
                return get_string('not_coursecat_context', 'local_inscricoes');
            }
        } catch (dml_write_exception $e) {
            return '40-' . $e->getMessage() . ' - ' . $e->debuginfo;
        } catch (Exception $e) {
            return $e->getMessage();
        }

        return get_string('ok', 'local_inscricoes');
    }

    public static function unsubscribe_user_returns() {
        return new external_value(PARAM_TEXT, get_string('answer_text', 'local_inscricoes'));
    }

    // ---------------------------------------------------------------------------------------------

    public static function add_edition_parameters() {
        return new external_function_parameters (
                    array('activityid' => new external_value(PARAM_INT, 'Activity id'),
                          'editionid' => new external_value(PARAM_INT, 'Edition id'),
                          'editionname' => new external_value(PARAM_TEXT, 'Edition name')));
    }

    public static function add_edition($activityid, $editionid, $editionname) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::add_edition_parameters(),
                        array('activityid'=>$activityid, 'editionid'=>$editionid, 'editionname'=>$editionname));

        if(!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_not_configured', 'local_inscricoes');
        }
        if(!$activity->enable) {
            return get_string('activity_not_enable', 'local_inscricoes');
        }

        if($edition = $DB->get_record('inscricoes_editions', array('activityid'=>$activity->id, 'externaleditionid'=>$editionid))) {
            $edition->externaleditionname = $editionname;
            if($DB->update_record('inscricoes_editions', $edition)) {
                return get_string('ok', 'local_inscricoes');
            } else {
                return get_string('add_edition_fail', 'local_inscricoes');
            }
        } else {
            $edition = new stdclass();
            $edition->activityid = $activity->id;
            $edition->externaleditionid = $editionid;
            $edition->externaleditionname = $editionname;
            $edition->timecreated = time();
            if($DB->insert_record('inscricoes_editions', $edition)) {
                return get_string('ok', 'local_inscricoes');
            } else {
                return get_string('add_edition_fail', 'local_inscricoes');
            }
        }
    }

    public static function add_edition_returns() {
        return new external_value(PARAM_TEXT, get_string('answer_text', 'local_inscricoes'));
    }

    // ---------------------------------------------------------------------------------------------

    public static function get_user_status_parameters() {
        return new external_function_parameters (
                    array('idpessoa'   => new external_value(PARAM_LONG, 'Id Pessoa do SCCP'),
                          'activityid' => new external_value(PARAM_INT, 'Activity id')));
    }

    public static function get_user_status($idpessoa, $activityid) {
        global $DB;

        $params = self::validate_parameters(self::get_user_status_parameters(),
                        array('idpessoa'=>$idpessoa, 'activityid'=>$activityid));

        if(!$user = $DB->get_record('user', array('idnumber'=>$idpessoa))) {
            return array('userid'=>0, 'editions'=>array());
        }

        if(!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return array('userid'=>$user->id, 'editions'=>array());
        }

        $sql = "SELECT DISTINCT ie.externaleditionid
                 FROM {inscricoes_activities} ia
                 JOIN {inscricoes_editions} ie ON (ie.activityid = ia.id)
                 JOIN {cohort} ch ON (ch.contextid = ia.contextid AND ch.idnumber LIKE CONCAT ('si_%_edicao:', ie.externaleditionid))
                 JOIN {cohort_members} cm ON (cm.cohortid = ch.id)
                WHERE ia.id = :activityid
                  AND cm.userid = :userid";
        $editionids = $DB->get_records_sql($sql, array('activityid'=>$activity->id, 'userid'=>$user->id));
        return array('userid'=>$user->id, 'editions'=>array_keys($editionids));
    }

    public static function get_user_status_returns() {
        return new external_single_structure(
                    array(
                        'userid'   => new external_value(PARAM_INT, 'User id'),
                        'editions' => new external_multiple_structure(new external_value(PARAM_INT, 'Edition Id'), 'Array of Edition ids'),
                    )
               );
    }

}
