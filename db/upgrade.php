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

    if ($oldversion < 2014021004) {

        // ---------------------------------------------------------------------------
        $table = new xmldb_table('inscricoes_config_reports');

        $field = new xmldb_field('minoptionalcourses', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_precision($table, $field);

        $field = new xmldb_field('maxoptionalcourses', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_precision($table, $field);

        // ---------------------------------------------------------------------------
        $table = new xmldb_table('inscricoes_courses');

        $field = new xmldb_field('inscribestartdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'workload');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('inscribeenddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'inscribestartdate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('coursedependencyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'inscribeenddate');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_precision($table, $field);

        $field = new xmldb_field('workload', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, null);
        $dbman->change_field_precision($table, $field);

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2014021004, 'local', 'inscricoes');
    }

    return true;
}
