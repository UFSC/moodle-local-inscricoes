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

function local_inscricoes_get_reports($categoryid, $including_subcategories=false) {
    global $DB;

    $catids = local_inscricoes_get_related_categoryids($categoryid, $including_subcategories);
    $str_cats = implode(',', $catids);

    $sql = "SELECT ir.*
              FROM {inscricoes_reports} ir
              JOIN {context} ctx ON (ctx.id = ir.contextid)
             WHERE ctx.instanceid IN ($str_cats)
               AND ctx.contextlevel = :contextlevel";
    return $DB->get_records_sql($sql, array('contextlevel'=>CONTEXT_COURSECAT));
}

// return the courses that may be marked as optional or mandatory
function local_inscricoes_get_potential_courses($contextid, $category_path) {
    global $DB;

    $cats = explode('/', $category_path);
    unset($cats[0]);
    $str_cats = implode(',', $cats);
    $sql = "SELECT c.id, c.shortname, c.fullname,
                   ic.id AS icid, ic.type, ic.workload, ic.inscribestartdate, ic.inscribeenddate, ic.coursedependencyid
              FROM {course} c
         LEFT JOIN {inscricoes_courses} ic ON (ic.courseid = c.id)
         LEFT JOIN {inscricoes_reports} ir ON (ir.id = ic.reportid AND ir.contextid = :contextid)
             WHERE c.category IN ({$str_cats})
          ORDER BY c.fullname";
    return $DB->get_records_sql($sql, array('contextid'=>$contextid));
}

// return the courses marked as optional or mandatory
function local_inscricoes_get_active_courses($contextid, $order_by='fullname') {
    global $DB;

    $sql = "SELECT c.id, c.shortname, c.fullname,
                   ic.id AS icid, ic.type, ic.workload, ic.inscribestartdate, ic.inscribeenddate, ic.coursedependencyid
              FROM {inscricoes_reports} ir
              JOIN {inscricoes_courses} ic ON (ic.reportid = ir.id)
              JOIN {course} c ON (c.id = ic.courseid)
             WHERE ir.contextid = :contextid
               AND ic.type IN (1, 2)
          ORDER BY {$order_by}";
    return $DB->get_records_sql($sql, array('contextid'=>$contextid));
}

// retorna os nomes dos grupos contidos nos cursos informados aos quais o usuário tem acesso
function local_inscricoes_get_groups($course_ids, $context, $userid) {
    global $DB;

    if (has_capability('moodle/site:accessallgroups', $context)) {
        $join = '';
    } else {
        $join = "JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = {$userid})";
    }

    $str_ids = implode(',', $course_ids);
    $sql = "SELECT g.name, GROUP_CONCAT(g.id SEPARATOR ',') as str_groupids
              FROM {course} c
              JOIN {groups} g ON (g.courseid = c.id)
              {$join}
             WHERE c.id IN ({$str_ids})
          GROUP BY g.name";
    return $DB->get_records_sql($sql);
}

function local_inscricoes_get_students($contextid, $str_groupids, $days_before, $studentsorderby) {
    global $DB;

    $timebefore = strtotime('-' . $days_before . ' days');
    $params = array('contextlevel'=>CONTEXT_COURSE, 'contextid'=>$contextid, 'timebefore'=>$timebefore);

    $sql = "SELECT ia.studentroleid
              FROM inscricoes_reports ir
              JOIN inscricoes_activities ia ON (ia.id = ir.activityid)
             WHERE ir.contextid = :contextid";
    $ia = $DB->get_record_sql($sql, array('contextid'=>$contextid));
    $params['roleid'] = $ia->studentroleid;

    $order = $studentsorderby == 'lastaccess' ? 'last_access ASC' : 'fullname';
    $sql = "SELECT uj.id, uj.fullname, uj.str_courseids,
                   COUNT(*) as count_actions,
                   SUM(CASE WHEN l.time >= :timebefore THEN 1 ELSE 0 END) AS recent_actions,
                   MIN(l.time) as first_access,
                   MAX(l.time) as last_access
              FROM inscricoes_reports ir
              JOIN inscricoes_courses ic ON (ic.reportid = ir.id AND ic.type IN (1, 2))
              JOIN log l ON (l.course = ic.courseid)
              JOIN (SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as fullname,
                           GROUP_CONCAT(DISTINCT c.id SEPARATOR ',') as str_courseids
                      FROM groups g
                      JOIN groups_members gm ON (gm.groupid = g.id)
                      JOIN course c ON (c.id = g.courseid)
                      JOIN context ctx ON (ctx.contextlevel = :contextlevel AND ctx.instanceid = c.id)
                      JOIN role_assignments ra ON (ra.contextid = ctx.id AND ra.userid = gm.userid AND ra.roleid = :roleid)
                      JOIN user u ON (u.id = ra.userid)
                     WHERE g.id IN ({$str_groupids})
                     GROUP BY u.id
                   ) uj
                ON (l.userid = uj.id)
             WHERE ir.contextid = :contextid
          ORDER BY {$order}";
    return $DB->get_records_sql($sql, $params);
}

function local_inscricoes_get_all_students($contextid) {
    global $DB;

    $sql = "SELECT u.id, u.username, CONCAT(u.firstname, ' ', u.lastname) as fullname,
                   GROUP_CONCAT(DISTINCT g.name SEPARATOR ';') as groupnames
              FROM inscricoes_reports ir
              JOIN inscricoes_activities ia ON (ia.id = ir.activityid)
              JOIN inscricoes_courses ic ON (ic.reportid = ir.id AND ic.type IN (1, 2))
              JOIN {context} ctx ON (ctx.contextlevel = :contextlevel AND ctx.instanceid = ic.courseid)
              JOIN {role_assignments} ra ON (ra.contextid = ctx.id AND ra.roleid = ia.studentroleid)
              JOIN user u ON (u.id = ra.userid)
              JOIN {groups} g ON (g.courseid = ic.courseid)
              JOIN {groups_members} gm ON (gm.groupid = g.id AND gm.userid = u.id)
             WHERE ir.contextid = :contextid
          GROUP BY u.id
          ORDER BY firstname, lastname";
    $params = array('contextid'=>$contextid, 'contextlevel'=>CONTEXT_COURSE);
    return $DB->get_records_sql($sql, $params);
}

function local_inscricoes_add_user($idpessoa) {
    global $DB, $CFG;

    if(!$user = $DB->get_record('user', array('username'=>$idpessoa))) {
        try {
            sccp::conecta();
            if(!$pessoa = sccp::obtem_pessoa($idpessoa)) {
                throw new Exception(get_string('idpessoa_unknown', 'local_inscricoes'));
            }
        } catch (Exception $e) {
            throw new Exception(get_string('connection_fail', 'local_inscricoes'));
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

function local_inscricoes_add_cohort_member($contextid, $userid, $role, $edition, $createcohortbyedition=false) {
    $cohort = local_inscricoes_add_cohort($role, $role, $contextid);
    cohort_add_member($cohort->id, $userid);

    if($createcohortbyedition) {
        $name = $role . ': ' . $edition;
        $cohort = local_inscricoes_add_cohort($name, $name, $contextid);
        cohort_add_member($cohort->id, $userid);
    }
}

function local_inscricoes_add_cohort($name, $idnumber, $contextid) {
    global $DB;

    $name = addslashes($name);
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

    $DB->delete_records('inscricoes_user_fields', array('activityid'=>$activityid, 'userid'=>$userid));
    $fields = json_decode($aditional_fields, true);
    if(!empty($fields)) {
        $insc = new stdClass();
        $insc->activityid = $activityid;
        $insc->userid = $userid;
        $insc->timemodified = time();
        foreach($fields AS $name=>$value) {
            $insc->name = $name;
            $insc->value = $value;
            $DB->insert_record('inscricoes_user_fields', $insc);
        }
    }
}

function local_inscricoes_get_aditional_fields($contextid, $userid) {
    global $DB;

    $sql = "SELECT name, value
              FROM (SELECT name, value
                      FROM {inscricoes_user_fields} uf
                      JOIN {inscricoes_activities} a ON (a.id = uf.activityid)
                     WHERE a.contextid = :contextid
                       AND uf.userid = :userid
                    ORDER BY name, timemodified DESC) ad
            GROUP BY name";
    return $DB->get_records_sql($sql, array('contexti'=>$contextid, 'userid'=>$userid));
}
