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

    return true;
}
