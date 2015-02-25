<?php

function xmldb_local_inscricoes_upgrade($oldversion) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2015012900) {

        // --------------------------------------------------------------------------------------------
        $table = new xmldb_table('inscricoes_cohorts');
        if (!$dbman->table_exists($table)) {
            $dbman->install_one_table_from_xmldb_file(dirname(__FILE__). '/install.xml', 'inscricoes_cohorts');
        }

        $table = new xmldb_table('inscricoes_activities');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('externalactivityname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'externalactivityid');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $table = new xmldb_table('inscricoes_editions');
        if ($dbman->table_exists($table)) {
            $sql = "UPDATE {inscricoes_editions} ie
                      JOIN {inscricoes_activities} ia ON (ia.id = ie.activityid)
                       SET ia.externalactivityname = CONCAT('Atividade ', ia.externalactivityid, ' (', ie.externaleditionname, ')')";
            $DB->execute($sql);
        }

        // --------------------------------------------------------------------------------------------
        $table = new xmldb_table('inscricoes_editions');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2015012900, 'local', 'inscricoes');
    }

    return true;
}
