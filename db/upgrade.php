<?php

/**
 * Handles upgrading instances of this block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_local_inscricoes_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    if ($oldversion < 2014040101) {
        // Define field display to be added to folder
        $table = new xmldb_table('inscricoes_reports');
        $field = new xmldb_field('tutornotesassign', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'optionalatonetime');

        // Conditionally launch add field display
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // folder savepoint reached
        upgrade_plugin_savepoint(true, 2014040101, 'local', 'inscricoes');
    }

    if ($oldversion < 2014040304) {
        // Define field display to be added to folder
        $table = new xmldb_table('inscricoes_editions');
        $field = new xmldb_field('externalactivityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'id');

        // Conditionally launch add field display
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'activityid');
        }

        // folder savepoint reached
        upgrade_plugin_savepoint(true, 2014040304, 'local', 'inscricoes');
    }

    if ($oldversion < 2014060101) {
        // Define field display to droped
        $table = new xmldb_table('inscricoes_activities');
        $field = new xmldb_field('createcohortbyedition');

        // Conditionally launch add field display
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // folder savepoint reached
        upgrade_plugin_savepoint(true, 2014060101, 'local', 'inscricoes');
    }

    return true;
}
