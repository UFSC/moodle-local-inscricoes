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

class local_inscricoes {

    /**
     * Returns the cohort idnumber
     *
     * @param String $role_shortname
     * @param stdclass $edition
     * @return String
     */
    public static function cohort_idnumber($role_shortname='student', $edition=false) {
        if($edition) {
            return "si_{$role_shortname}_edicao:{$edition->externaleditionid}";
        } else {
            return "si_{$role_shortname}";
        }
    }

    /**
     * Returns the cohort name
     *
     * @param String $role_name
     * @param stdclass $edition
     * @return String
     */
    public static function cohort_name($role_name, $edition=false) {
        if($edition) {
            return "{$role_name}: {$edition->externaleditionname} (SI)";
        } else {
            return "{$role_name} (SI)";
        }
    }

    /**
     * Returns the sql cohort idnumber
     *
     * @param String $table_prefix
     * @param stdclass $edition
     * @param String $role_shortname
     * @return String
     */
    public static function cohort_idnumber_sql($table_prefix='', $edition=false, $role_shortname='student') {
        $prefix = empty($table_prefix) ? '' : $table_prefix . '.';
        if($edition) {
            return "CONCAT('si_{$role_shortname}_edicao:', {$prefix}externaleditionid)";
        } else {
            return "'si_{$role_shortname}'";
        }
    }
}
