<?php

defined('MOODLE_INTERNAL') || die;

require_once("{$CFG->libdir}/externallib.php");
require_once("{$CFG->dirroot}/local/inscricoes/classes/inscricoes.php");

class local_inscricoes_external extends external_api {

// ----------------------------------------------------------------------------------

    public static function get_categories_parameters() {
        return new external_function_parameters(
                    array('idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP')));
    }

    /**
     * Retorna uma lista de categorias nas quais a pessoa (identificada pelo idpessoa) tem permissão para associar
     * atividades do Sistema de Inscrições.
     *
     * @param  int $idpessoa
     * @return array the category details
     */
    public static function get_categories($idpessoa) {
        global $DB;

        $params = self::validate_parameters(self::get_categories_parameters(), array('idpessoa'=>$idpessoa));

        if (!$userid = $DB->get_field('user', 'id', array('idnumber'=>$idpessoa))) {
            return array();
        }

        $sql = "SELECT DISTINCT cc.id, cc.name
                  FROM {context} ctx
                  JOIN {role_assignments} ra ON (ra.contextid = ctx.id)
                  JOIN {course_categories} cc ON (cc.id = ctx.instanceid)
                 WHERE ctx.contextlevel = 40
                   AND ra.userid = {$userid}
              ORDER BY cc.name";

        $categories = array();
        foreach ($DB->get_recordset_sql($sql) AS $cat) {
            $context = context_coursecat::instance($cat->id);
            if (has_capability('local/inscricoes:manage', $context, $userid)){
                $categories[] = $cat;
            }
        }

        return $categories;
    }

    public static function get_categories_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'       => new external_value(PARAM_INT, 'Category id'),
                            'name'     => new external_value(PARAM_TEXT, 'Category name'),
                        )
                    )
               );
    }

// ----------------------------------------------------------------------------------

    public static function get_roles_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * Retorna uma lista de papeis que podem ser utilizados pelo Sistema de Inscrições para inscrever pessoas no Moodle.
     *
     * @return array lista de papeis
     */
    public static function get_roles() {
        $roles = array();
        foreach (local_inscricoes::get_roles() AS $role) {
            $r = new stdClass();
            $r->shortname = $role->shortname;
            $r->name      = $role->name;
            $roles[] = $r;
        }

        return $roles;
    }

    public static function get_roles_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'shortname' => new external_value(PARAM_TEXT, 'Role shortname'),
                            'name'      => new external_value(PARAM_TEXT, 'Role name'),
                        )
                    )
               );
    }

// ----------------------------------------------------------------------------------

    public static function add_activity_parameters() {
        return new external_function_parameters(
                    array('idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP'),
                          'categoryid' => new external_value(PARAM_INT, 'Category id'),
                          'activityid' => new external_value(PARAM_INT, 'Activity id'),
                          'activityname' => new external_value(PARAM_TEXT, 'Activity name'),
                          'roles' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Role shortname'), '', VALUE_DEFAULT, array())
                         )
                   );
    }

    /**
     * Relaciona uma atividade do Sistema de Inscrições com uma categoria do Moodle e, opcionalmente, cria os cohorts de usuários por papel
     *
     * @param  int $idpessoa da pessoa com permissão para associar atividades do Sistema de Inscrições com classes do Moodle
     * @param  int $categoryid id da categoria à qual será associada uma atividade do Sistema de Inscrições
     * @param  int $activityid id da atividade do Sistema de Inscrições que será associada à categoria
     * @param  int $activityname nome da atividade do Sistema de Inscrições que será associada à categoria
     * @param  array $role_shortnames lista (opcional) de papeis com os quais as pessoas serão inscritas
     *                                (utilizado para criação inicial dos cohorts por papel)
     * @return String mensagem de ok ou de erro
     */
    public static function add_activity($idpessoa, $categoryid, $activityid, $activityname, $role_shortnames=array()) {
        global $DB;

        $params = self::validate_parameters(self::add_activity_parameters(),
                        array('idpessoa'=>$idpessoa, 'categoryid'=>$categoryid, 'activityid'=>$activityid, 'activityname'=>$activityname, 'roles'=>$role_shortnames));

        if (!$userid = $DB->get_field('user', 'id', array('idnumber'=>$idpessoa))) {
            return get_string('idpessoa_unknown', 'local_inscricoes');
        }

        if (!$DB->record_exists('course_categories', array('id'=>$categoryid))) {
            return get_string('category_unknown', 'local_inscricoes');
        }

        $context = context_coursecat::instance($categoryid);
        if (!has_capability('local/inscricoes:manage', $context, $userid)){
            return get_string('no_permission', 'local_inscricoes');
        }

        if ($activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_already_configured', 'local_inscricoes');
        }

        if (!empty($role_shortnames)) {
            $roles = local_inscricoes::get_roles();
            foreach ($role_shortnames as $role_shortname) {
                if (!isset($roles[$role_shortname])) {
                    return get_string('role_invalid', 'local_inscricoes');
                }
            }
        }

        $activity = new stdclass();
        $activity->contextid = $context->id;
        $activity->externalactivityid = $activityid;
        $activity->externalactivityname = $activityname;
        $activity->enable = 1;
        $activity->timecreated = time();
        if (!$id = $DB->insert_record('inscricoes_activities', $activity)) {
            return get_string('add_activity_fail', 'local_inscricoes');
        }
        $activity->id = $id;

        if (!empty($role_shortnames)) {
            foreach ($role_shortnames as $role_shortname) {
                local_inscricoes::get_cohort($activity, $roles[$role_shortname], true);
            }
        }

        return get_string('ok', 'local_inscricoes');
    }

    public static function add_activity_returns() {
        return new external_value(PARAM_TEXT, get_string('answer_text', 'local_inscricoes'));
    }

// ----------------------------------------------------------------------------------

    public static function subscribe_user_parameters() {
        return new external_function_parameters(
                    array('activityid' => new external_value(PARAM_INT, 'Activity Id'),
                          'idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP'),
                          'role' => new external_value(PARAM_TEXT, 'Subscription role shortname'),
                          'aditional_fields' => new external_value(PARAM_TEXT, 'Aditional fields (JSON - [name:key:value]*')));
    }

    /**
     * Inscreve um usuário no cohort correspondente ao papel da categoria correspondente à atividade do Sistema de Inscrições
     *
     * @param  int $activityid id da atividade do Sistema de Inscrições
     * @param  int $idpessoa da pessoa a ser inscrita
     * @param  String $role_shortname nome curto do papel com o qual a pessoa será inscrita
     * @param  Array $aditional_fields campos opcionais adicionais (chave - valor) relativo à inscrição da pessoa
     * @return String ensagem de ok ou de erro
     */
    public static function subscribe_user($activityid, $idpessoa, $role_shortname, $aditional_fields) {
        global $DB;

        $params = self::validate_parameters(self::subscribe_user_parameters(),
                        array('activityid'=>$activityid, 'idpessoa'=>$idpessoa, 'role'=>$role_shortname, 'aditional_fields'=>$aditional_fields));

        if (!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_not_configured', 'local_inscricoes');
        }
        if (!$activity->enable) {
            return get_string('activity_not_enable', 'local_inscricoes');
        }

        if (!$context = context::instance_by_id($activity->contextid)) {
            return get_string('category_unknown', 'local_inscricoes');
        }

        $roles = local_inscricoes::get_roles();
        if (!isset($roles[$role_shortname])) {
            return get_string('role_invalid', 'local_inscricoes');
        }

        try {
            $user = local_inscricoes::add_user($idpessoa);
            local_inscricoes::add_cohort_member($activity, $roles[$role_shortname], $user->id);
            local_inscricoes::add_additional_fields($activity->id, $user->id, $aditional_fields);
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
        return new external_function_parameters(
                    array('activityid' => new external_value(PARAM_INT, 'Activity Id'),
                          'idpessoa' => new external_value(PARAM_LONG, 'Id Pessoa do SCCP')));
    }

    /**
     * Desinscreve um usuário de todos os cohorts (papeis) da categoria correspondente à atividade do Sistema de Inscrições
     *
     * @param  int $activityid id da atividade do Sistema de Inscrições
     * @param  int $idpessoa da pessoa a ser desinscrita
     * @return String ensagem de ok ou de erro
     */
    public static function unsubscribe_user($activityid, $idpessoa) {
        global $DB;

        $params = self::validate_parameters(self::unsubscribe_user_parameters(),
                        array('activityid'=>$activityid, 'idpessoa'=>$idpessoa));

        if (!$activity = $DB->get_record('inscricoes_activities', array('externalactivityid'=>$activityid))) {
            return get_string('activity_not_configured', 'local_inscricoes');
        }
        if (!$activity->enable) {
            return get_string('activity_not_enable', 'local_inscricoes');
        }

        if (!$context = context::instance_by_id($activity->contextid)) {
            return get_string('category_unknown', 'local_inscricoes');
        }

        if (!$user = $DB->get_record('user', array('idnumber'=>$idpessoa))) {
            return get_string('idpessoa_unknown', 'local_inscricoes');
        }

        try {
            local_inscricoes::remove_cohort_member($activity, $user->id);
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

    public static function get_user_activities_parameters() {
        return new external_function_parameters(
                    array('idpessoa'   => new external_value(PARAM_LONG, 'Id Pessoa do SCCP')));
    }

    /**
     * Retorna uma lista com todas as atividades onde uma pessoa está inscrita
     *
     * @param  int $idpessoa da pessoa
     * @return array detalhes das atividades
     */
    public static function get_user_activities($idpessoa) {
        global $DB;

        $params = self::validate_parameters(self::get_user_activities_parameters(), array('idpessoa'=>$idpessoa));

        if (!$user = $DB->get_record('user', array('idnumber'=>$idpessoa))) {
            return array();
        }

        $activities = array();
        foreach (local_inscricoes::get_user_activities($user->id) AS $rec) {
            $act = new stdClass();
            $act->activityid = $rec->id;
            $act->externalactivityid = $rec->externalactivityid;
            $act->externalactivityname = $rec->externalactivityname;
            $act->categoryid = $rec->categoryid;
            $act->categoryname = $rec->name;
            $act->role = $rec->role_shortname;
            $activities[] = $act;
        }
        return $activities;
    }

    public static function get_user_activities_returns() {
        return new external_multiple_structure(
                    new external_single_structure(
                        array('activityid' => new external_value(PARAM_INT, 'User id'),
                              'externalactivityid' => new external_value(PARAM_INT, 'Id da atividade no Sistema de Inscrições'),
                              'externalactivityname' => new external_value(PARAM_TEXT, 'Nome da atividade no Sistema de Inscrições'),
                              'categoryid' => new external_value(PARAM_INT, 'Category id'),
                              'categoryname' => new external_value(PARAM_TEXT, 'Nome da categoria no Moodle'),
                              'role'       => new external_value(PARAM_TEXT, 'Role shortname'),
                            )
                        )
               );
    }
}
