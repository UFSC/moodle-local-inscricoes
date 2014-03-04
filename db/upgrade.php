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

    if ($oldversion < 2014021009) {

        // ---------------------------------------------------------------------------
        $table = new xmldb_table('inscricoes_editions');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('externalactivityid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('externaleditionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('externaleditionname', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('activityid-editionid', XMLDB_KEY_UNIQUE, array('externalactivityid', 'externaleditionid'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2014021009, 'local', 'inscricoes');
    }

    return true;
}
