<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Event handler definition
 *
 * @package local_inscricoes
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/local/wsufsc/lib.php');

class local_inscricoes {

    public static function get_activities($categoryid, $only_enable=false, $including_subcategories=false) {
        global $DB;

        $catids = self::get_related_categoryids($categoryid, $including_subcategories);
        $str_cats = implode(',', $catids);

        $sql = "SELECT ia.*
            FROM {inscricoes_activities} ia
            JOIN {context} ctx ON (ctx.id = ia.contextid)
            WHERE ctx.instanceid IN ($str_cats)
            AND ctx.contextlevel = :contextlevel";
        if($only_enable) {
            $sql .= " AND ia.enable = 1";
        }
        return $DB->get_records_sql($sql, array('contextlevel'=>CONTEXT_COURSECAT));
    }

    public static function get_related_categoryids($categoryid, $including_children=false) {
        global $DB;

        $path = $DB->get_field('course_categories', 'path', array('id'=>$categoryid));
        $catids = explode('/', $path);
        unset($catids[0]);

        if($including_children) {
            $sql = "SELECT id FROM {course_categories} WHERE path LIKE '%/{$categoryid}/%'";
            $catids = array_merge($catids, array_keys($DB->get_records_sql($sql)));
        }
        return $catids;
    }

    /**
     * Returns an array of valid roles
     *
     * @return array
     */
    public static function get_roles() {
        $cfg_roles = get_config('local_inscricoes', 'roles');
        if(empty($cfg_roles)) {
            return array();
        }

        $cfg_roles = explode(',', $cfg_roles);
        $allroles = role_get_names(context_system::instance());

        $roles = array();
        foreach ($cfg_roles AS $roleid) {
            $r = new stdClass();
            $r->id        = $roleid;
            $r->shortname = $allroles[$roleid]->shortname;
            $r->name      = $allroles[$roleid]->localname;
            $roles[$r->shortname] = $r;
        }

        return $roles;
    }

    /**
     * Returns an user base on his $idpessoa. A new user is create if necessary.
     *
     * @param int $idpessoa
     * @return stdClass
     */
    public static function add_user($idpessoa) {
        global $DB, $CFG;

        if(!$user = $DB->get_record('user', array('idnumber'=>$idpessoa))) {
            $wsufsc = new wsufsc();
            if(!$pessoa = $wsufsc->getPessoaById($idpessoa)) {
                throw new Exception(get_string('idpessoa_unknown', 'local_inscricoes'));
            }
            if(empty($pessoa->email)) {
                throw new Exception(get_string('email_empty', 'local_inscricoes'));
            }
            if(isset($pessoa->cpf)) {
                if($user = $DB->get_record('user', array('username'=>$pessoa->cpf))) {
                    local_wsufsc_atualiza_usuario($user, $pessoa);
                }
            }
        }

        if(!$user) {
            $user = new stdClass();
            $user->email      = $pessoa->email;
            $user->confirmed  = 1;
            $user->mnethostid = $CFG->mnet_localhost_id;
            $user->auth       = 'cas_ufsc';
            $user->username   = isset($pessoa->cpf) ? $pessoa->cpf : $idpessoa;
            $user->idnumber   = $idpessoa;

            $pos = strrpos($pessoa->nome, ' ');
            if ($pos === false) {
                $user->firstname = $pessoa->nome;
                $user->lastname = '';
            } else {
                $user->firstname = substr($pessoa->nome, 0, $pos);
                $user->lastname = substr($pessoa->nome, $pos+1);
            }
            if($id = user_create_user($user, true)) {
                $user->id = $id;
            } else {
                throw new Exception(get_string('add_user_fail', 'local_inscricoes'));
            }
        }
        return $user;
    }

    /**
     * Add a member to a cohort related to an activity and role. Create a new cohort if necessary.
     *
     * @param stdClass $activity
     * @param stdClass $role
     * @param int $userid
     * @return null
     */
    public static function add_cohort_member($activity, $role, $userid) {
        $cohort = self::get_cohort($activity, $role, true);
        cohort_add_member($cohort->id, $userid);
    }

    /**
     * Remove a member from all cohorts (all possible roles) related to an activity.
     *
     * @param stdClass $activity
     * @param int $userid
     * @return null
     */
    public static function remove_cohort_member($activity, $userid) {
        global $DB;

        $insc_cohorts = $DB->get_records('inscricoes_cohorts', array('activityid'=>$activity->id));
        foreach($insc_cohorts AS $ic) {
            cohort_remove_member($ic->cohortid, $userid);
        }
    }

    /**
     * Add addional fields to the user related to an activity.
     *
     * @param stdClass $activity
     * @param int $userid
     * @param string $aditional_fields (a json string)
     * @return null
     */
    public static function add_additional_fields($activityid, $userid, $aditional_fields) {
        global $DB;

        $sql = "DELETE {inscricoes_user_fields}
                  FROM {inscricoes_user_fields}
                  JOIN {inscricoes_key_fields} ikf ON (ikf.id = keyid)
                 WHERE userid = :userid
                   AND activityid = :activityid";
        $DB->execute($sql, array('activityid'=>$activityid, 'userid'=>$userid));

        $fields = json_decode($aditional_fields, true);
        if(!empty($fields)) {
            $insc = new stdClass();
            $insc->userid = $userid;
            $insc->timemodified = time();

            $added_fields = array();
            $has_empty_field = false;
            $has_duplicated_field = false;
            foreach($fields AS $f) {
                if(count($f) >= 2) {
                    $field = trim($f[0]);
                    $name  = trim($f[1]);
                    if(empty($field) || empty($name)) {
                        $has_empty_field = true;
                    } else if(isset($added_fields[$field])) {
                        $has_duplicated_field = true;
                    } else {
                        $value = count($f) > 2 ? $f[2] : $f[1];
                        if($key = $DB->get_record('inscricoes_key_fields', array('activityid'=>$activityid, 'field'=>$field, 'name'=>$name))) {
                            if($value != $key->value) {
                                $new_key = new stdClass();
                                $new_key->id = $key->id;
                                $new_key->value = $value;
                                $new_key->timemodified = time();
                                $DB->update_record('inscricoes_key_fields', $new_key);
                            }
                        } else {
                            $key = new stdClass();
                            $key->activityid = $activityid;
                            $key->field = $field;
                            $key->name = $name;
                            $key->value = $value;
                            $key->timemodified = time();
                            $key->id = $DB->insert_record('inscricoes_key_fields', $key);
                        }
                        $insc->keyid = $key->id;
                        $DB->insert_record('inscricoes_user_fields', $insc);
                        $added_fields[$field]=true;
                    }
                }
            }
            if($has_empty_field) {
                throw new Exception(get_string('has_empty_field', 'local_inscricoes'));
            } else if($has_duplicated_field) {
                throw new Exception(get_string('has_duplicated_field', 'local_inscricoes'));
            }
        }
    }

    // -----------------------------------------------------------------------------------------------

    /**
     * Returns the cohort in the contextid associated to the role and activity.
     * A new cohort is created if it doesn't exists.
     *
     * @param stdclass $activity
     * @param stdclass $role
     * @param boolean $create Create a new cohort if it doesn't exists
     * @return stdclass
     */
    public static function get_cohort($activity, $role, $create=false) {
        global $DB;

        if($cohortid = $DB->get_field('inscricoes_cohorts', 'cohortid', array('activityid'=>$activity->id, 'roleid'=>$role->id))) {
            return $DB->get_record('cohort', array('id'=>$cohortid), '*', MUST_EXIST);
        }

        if($create) {
            $cohort = new stdClass();
            $cohort->contextid = $activity->contextid;
            $cohort->name = self::cohort_name($activity, $role->name);
            $cohort->idnumber = self::cohort_idnumber($activity, $role->shortname);
            $cohort->component = 'local_inscricoes';
            $id = cohort_add_cohort($cohort);
            $cohort->id = $id;

            $act_cohort = new stdClass();
            $act_cohort->activityid = $activity->id;
            $act_cohort->cohortid = $cohort->id;
            $act_cohort->roleid = $role->id;
            $DB->insert_record('inscricoes_cohorts', $act_cohort);
        }
        return $cohort;
    }

    /**
     * Returns the cohort idnumber
     *
     * @param stdclass $activity
     * @param String $role_shortname
     * @return String
     */
    public static function cohort_idnumber($activity, $role_shortname) {
        return "si_activity={$activity->externalactivityid}_role={$role_shortname}";
    }

    /**
     * Returns the cohort name
     *
     * @param stdclass $activity
     * @param String $role_shortname
     * @return String
     */
    public static function cohort_name($activity, $role_name) {
        return "{$role_name}: {$activity->externalactivityname} (SI)";
    }

    /**
     * Returns activities (and corresponding role shortname and categoryid) where the user is subscribed 
     *
     * @param int $userid
     * @return array
     */
    public static function get_user_activities($userid) {
        global $DB;

        $sql = "SELECT ia.*, ic.roleid, r.shortname as role_shortname, cc.id AS categoryid, cc.name
                  FROM {inscricoes_activities} ia
                  JOIN {inscricoes_cohorts} ic ON (ic.activityid = ia.id)
                  JOIN {role} r ON (r.id = ic.roleid)
                  JOIN {cohort} ch ON (ch.id = ic.cohortid)
                  JOIN {cohort_members} cm ON (cm.cohortid = ch.id)
                  JOIN {context} ctx ON (ctx.id = ia.contextid AND ctx.contextlevel = :contextlevel)
                  JOIN {course_categories} cc ON (cc.id = ctx.instanceid)
                 WHERE cm.userid = :userid
              ORDER BY ia.externalactivityname";
        $activities = array();
        $params = array('userid'=>$userid, 'contextlevel'=>CONTEXT_COURSECAT);
        foreach($DB->get_recordset_sql($sql, $params) AS $act) {
            $activities[] = $act;
        }
        return $activities;
    }

    public static function get_users($activityid, $roleid=0) {
        global $DB;

        $params = array('activityid'=>$activityid);

        $where_role = '';
        if(!empty($roleid)) {
            $where_role = 'AND r.id = :roleid';
            $params['roleid'] = $roleid;
        }

        $user_fields = implode(',', get_all_user_name_fields());
        $sql = "SELECT cm.userid, u.username, {$user_fields}, ic.roleid
                  FROM {inscricoes_activities} ia
                  JOIN {inscricoes_cohorts} ic ON (ic.activityid = ia.id)
                  JOIN {role} r ON (r.id = ic.roleid)
                  JOIN {cohort} ch ON (ch.id = ic.cohortid)
                  JOIN {cohort_members} cm ON (cm.cohortid = ch.id)
                  JOIN {user} u ON (u.id = cm.userid)
                 WHERE ia.id = :activityid
                   {$where_role}
              ORDER BY r.name, u.firstname, u.lastname";
        return $DB->get_records_sql($sql, $params);
    }

    public static function get_additional_fields($activityid) {
        global $DB;

        $sql = "SELECT DISTINCT field, field as value
                  FROM {inscricoes_key_fields}
                 WHERE activityid = :activityid
              ORDER BY field";
        return $DB->get_records_sql_menu($sql, array('activityid'=>$activityid));
    }

    public static function get_additional_field_values($activityid) {
        global $DB;

        $sql = "SELECT iuf.userid, ikf.field, ikf.name, ikf.value
                  FROM inscricoes_key_fields ikf
                  JOIN inscricoes_user_fields iuf ON (iuf.keyid = ikf.id)
                 WHERE ikf.activityid = :activityid";
        $rs = $DB->get_recordset_sql($sql, array('activityid'=>$activityid));
        $values = array();
        foreach($rs AS $r) {
            $values[$r->userid][$r->field] = array($r->name, $r->value);
        }

        return $values;
    }
}
