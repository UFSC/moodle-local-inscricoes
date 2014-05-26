<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/local/sccp/lib.php');

// return categoryids above and below $categoryid, including $categoryid
function local_inscricoes_get_related_categoryids($categoryid, $including_children=false) {
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

function local_inscricoes_get_activities($categoryid, $only_enable=false, $including_subcategories=false) {
    global $DB;

    $catids = local_inscricoes_get_related_categoryids($categoryid, $including_subcategories);
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

function local_inscricoes_get_editions($actiityid) {
    global $DB;

    return $DB->get_records('inscricoes_editions', array('activityid'=>$actiityid));
}

function local_inscricoes_add_user($idpessoa) {
    global $DB, $CFG;

    if(!$user = $DB->get_record('user', array('username'=>$idpessoa))) {
        if(!$pessoa = sccp::obtem_pessoa($idpessoa)) {
            throw new Exception(get_string('idpessoa_unknown', 'local_inscricoes'));
        }
        if(empty($pessoa->email)) {
            throw new Exception(get_string('email_empty', 'local_inscricoes'));
        }
        if(isset($pessoa->cpf)) {
            $user = $DB->get_record('user', array('username'=>$pessoa->cpf));
        }
    }

    if($user) {
        if($user->idnumber != $idpessoa) {
            $upuser = new stdClass();
            $upuser->id = $user->id;
            $upuser->idnumber = $idpessoa;
            $DB->update_record('user', $upuser);
        }
    } else {
        $user = new stdClass();
        $user->email      = $pessoa->email;
        $user->modified   = time();
        $user->confirmed  = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->lang       = $CFG->lang;
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
        if ($id = $DB->insert_record('user', $user)) {
            $user->id = $id;
        } else {
            throw new Exception(get_string('add_user_fail', 'local_inscricoes'));
        }
    }
    return $user;
}

function local_inscricoes_cohort_idnumber_sql($table_prefix='', $edition=false, $role_shortname='student') {
    $prefix = empty($table_prefix) ? '' : $table_prefix . '.';
    if($edition) {
        return "CONCAT('si_{$role_shortname}_edicao:', {$prefix}externaleditionid)";
    } else {
        return "'si_{$role_shortname}'";
    }
}

function local_inscricoes_cohort_idnumber($role_shortname, $edition=false) {
    if($edition) {
        return "si_{$role_shortname}_edicao:{$edition->externaleditionid}";
    } else {
        return "si_{$role_shortname}";
    }
}

function local_inscricoes_cohort_name($role_name, $edition=false) {
    if($edition) {
        return "{$role_name}: {$edition->externaleditionname} (SI)";
    } else {
        return "{$role_name} (SI)";
    }
}

function local_inscricoes_add_cohort_member($contextid, $userid, $role, $edition, $createcohortbyedition=false) {
    $role_name = role_get_name($role);

    $name = local_inscricoes_cohort_name($role_name);
    $idnumber = local_inscricoes_cohort_idnumber($role->shortname);
    $cohort = local_inscricoes_add_cohort($name, $idnumber, $contextid);
    cohort_add_member($cohort->id, $userid);

    if($createcohortbyedition) {
        $name = local_inscricoes_cohort_name($role_name, $edition);
        $idnumber = local_inscricoes_cohort_idnumber($role->shortname, $edition);
        $cohort = local_inscricoes_add_cohort($name, $idnumber, $contextid);
        cohort_add_member($cohort->id, $userid);
    }
}

function local_inscricoes_remove_cohort_member($activityid, $contextid, $userid, $role, $edition, $createcohortbyedition=false) {
    global $DB;

    $has_edition = false;
    if($createcohortbyedition) {
        $idnumber = local_inscricoes_cohort_idnumber($role->shortname, $edition);
        if($cohort = $DB->get_record('cohort', array('contextid'=>$contextid, 'idnumber'=>$idnumber))) {
            if(cohort_is_member($cohort->id, $userid)) {
                cohort_remove_member($cohort->id, $userid);
            }
        }

        $editions = $DB->get_records('inscricoes_editions', array('activityid'=>$activityid));
        foreach($editions AS $ed) {
            $idnumber = local_inscricoes_cohort_idnumber($role->shortname, $ed);
            if($cohort = $DB->get_record('cohort', array('contextid'=>$contextid, 'idnumber'=>$idnumber))) {
                if(cohort_is_member($cohort->id, $userid)) {
                    $has_edition = true;
                    break;
                }
            }
        }
    }

    $idnumber = local_inscricoes_cohort_idnumber($role->shortname);
    if($cohort = $DB->get_record('cohort', array('contextid'=>$contextid, 'idnumber'=>$idnumber))) {
        if(!$has_edition && cohort_is_member($cohort->id, $userid)) {
            cohort_remove_member($cohort->id, $userid);
        }
    }
}

function local_inscricoes_add_cohort($name, $idnumber, $contextid) {
    global $DB;

    if(!$cohort = $DB->get_record('cohort', array('contextid'=>$contextid, 'idnumber'=>$idnumber))) {
        $cohort = new stdClass();
        $cohort->contextid = $contextid;
        $cohort->name = $name;
        $cohort->idnumber = $idnumber;
        $cohort->component = 'local_inscricoes';
        $id = cohort_add_cohort($cohort);
        $cohort->id = $id;
    }
    return $cohort;
}

function local_inscricoes_add_aditional_fields($activityid, $userid, $aditional_fields) {
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
        foreach($fields AS $f) {
            if(count($f) >= 2) {
                $field  = trim($f[0]);
                $name   = trim($f[1]);
                if(!empty($field) &&  !empty($name) && !isset($added_fields[$field])) {
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
    }
}

function local_inscricoes_get_aditional_fields($contextid, $userid) {
    global $DB;

    $sql = "SELECT name, key, value
              FROM (SELECT name, key, value
                      FROM {inscricoes_user_fields} uf
                      JOIN {inscricoes_activities} a ON (a.id = uf.activityid)
                     WHERE a.contextid = :contextid
                       AND uf.userid = :userid
                    ORDER BY name, timemodified DESC) ad
            GROUP BY name";
    return $DB->get_records_sql($sql, array('contexti'=>$contextid, 'userid'=>$userid));
}
