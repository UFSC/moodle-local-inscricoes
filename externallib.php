<?php

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");
require_once("{$CFG->dirroot}/local/inscricoes/locallib.php");

class local_inscricoes_external extends external_api {

    public static function subscribe_user_parameters() {
        return new external_function_parameters (
                    array('activityid' => new external_value(PARAM_INT, 'Activity Id that comes fom de activity System'),
                          'idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP'),
                          'role' => new external_value(PARAM_TEXT, 'Subscription role'),
                          'edition' => new external_value(PARAM_TEXT, 'Activity ddição'),
                          'aditional_fields' => new external_value(PARAM_TEXT, 'Aditional fields (JSON - [name:value]*')));
    }

    public static function subscribe_user($activityid, $idpessoa, $role_shortname, $edition, $aditional_fields) {
        global $CFG, $DB;

        $params = self::validate_parameters(self::subscribe_user_parameters(),
                        array('activityid'=>$activityid, 'idpessoa'=>$idpessoa, 'role'=>$role_shortname,
                              'edition'=>$edition, 'aditional_fields'=>$aditional_fields));

        if(empty($role_shortname) || empty($edition)) {
            return get_string('empty_role_edition', 'local_inscricoes');
        }

        if(!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_not_configured', 'local_inscricoes');
        }
        if(!$activity->enable) {
            return get_string('activity_not_enable', 'local_inscricoes');
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
                local_inscricoes_add_cohort_member($context->id, $user->id, $role_shortname, $edition, $activity->createcohortbyedition);
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

}
