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
 * This file keeps track of upgrades to the ltiprovider plugin
 *
 * @package    local
 * @subpackage ent_installer
 * @copyright  2015 Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

function xmldb_local_ent_installer_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    $isent = is_dir($CFG->dirroot.'/local/ent_access_point');

    if ($oldversion < 2014061600) {

        $table = new xmldb_table('local_ent_installer');

        // Adding fields to table tool_ent_installer stat information.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, 0);
        $table->add_field('timerun', XMLDB_TYPE_INTEGER, 11, null, XMLDB_NOTNULL, null, 0);
        $table->add_field('added', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $table->add_field('updated', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $table->add_field('inserterrors', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        $table->add_field('updateerrors', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);

        // Adding keys to table tool_ent_installer.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for tool_ent_installer.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2014061600, 'local', 'ent_installer');
    }

    if ($oldversion < 2016050200) {

        if ($isent) {
            $categoryname = ent_installer_string('academicinfocategoryname');
            $academicinfocategoryid = $DB->get_field('user_info_category', 'id', array('name' => $categoryname));
            $lastorder = $DB->get_field('user_info_field', 'MAX(sortorder)', array('categoryid' => $academicinfocategoryid));

            // Adding primary assignation.
            // Primary assignation should be marked if the Moodle node
            // matches the registered primary facility of the user in ldap attributes.
            $userfield = new StdClass;
            $userfield->name = ent_installer_string('isprimaryassignation');
            $userfield->shortname = 'isprimaryassignation';
            $userfield->datatype = 'checkbox';
            $userfield->description = ent_installer_string('isprimaryassignation_desc');
            $userfield->descriptionformat = FORMAT_MOODLE;
            $userfield->categoryid = $academicinfocategoryid;
            $userfield->sortorder = $lastorder + 1;
            $userfield->required = 0;
            $userfield->locked = 1;
            $userfield->visible = 0;
            $userfield->forceunique = 0;
            $userfield->signup = 0;
            if (!$DB->record_exists('user_info_field', array('shortname' => 'isprimaryassignation'))) {
                $DB->insert_record('user_info_field', $userfield);
            }
        }

        upgrade_plugin_savepoint(true, 2016050200, 'local', 'ent_installer');
    }

    if ($oldversion < 2016090402) {

        if ($isent) {

            $categoryname = ent_installer_string('academicinfocategoryname');
            $academicinfocategoryid = $DB->get_field('user_info_category', 'id', array('name' => $categoryname));
            $lastorder = $DB->get_field('user_info_field', 'MAX(sortorder)', array('categoryid' => $academicinfocategoryid));

            // Adding primary assignation.
            // Primary assignation should be marked if the Moodle node.
            // matches the registered primary facility of the user in ldap attributes.
            $userfield = new StdClass;
            $userfield->name = ent_installer_string('personaltitle');
            $userfield->shortname = 'personaltitle';
            $userfield->datatype = 'text';
            $userfield->description = ent_installer_string('personaltitle_desc');
            $userfield->descriptionformat = FORMAT_MOODLE;
            $userfield->categoryid = $academicinfocategoryid;
            $userfield->sortorder = $lastorder + 1;
            $userfield->required = 0;
            $userfield->locked = 1;
            $userfield->visible = 0;
            $userfield->forceunique = 0;
            $userfield->signup = 0;
            $userfield->param1 = 10;
            $userfield->param2 = 10;
            if (!$DB->record_exists('user_info_field', array('shortname' => 'personaltitle'))) {
                $DB->insert_record('user_info_field', $userfield);
            }
        }

        upgrade_plugin_savepoint(true, 2016090402, 'local', 'ent_installer');
    }

    if ($oldversion < 2017062100) {

        if ($isent) {

            $categoryname = ent_installer_string('academicinfocategoryname');
            $academicinfocategoryid = $DB->get_field('user_info_category', 'id', array('name' => $categoryname));
            $lastorder = $DB->get_field('user_info_field', 'MAX(sortorder)', array('categoryid' => $academicinfocategoryid));

            // Adding primary assignation.
            // Primary assignation should be marked if the Moodle node.
            // matches the registered primary facility of the user in ldap attributes.
            $userfield = new StdClass;
            $userfield->name = ent_installer_string('fullage');
            $userfield->shortname = 'fullage';
            $userfield->datatype = 'checkbox';
            $userfield->description = ent_installer_string('fullage_desc');
            $userfield->descriptionformat = FORMAT_MOODLE;
            $userfield->categoryid = $academicinfocategoryid;
            $userfield->sortorder = $lastorder + 1;
            $userfield->required = 0;
            $userfield->locked = 1;
            $userfield->visible = 0;
            $userfield->forceunique = 0;
            $userfield->signup = 0;
            $userfield->param1 = 10;
            $userfield->param2 = 10;
            if (!$DB->record_exists('user_info_field', array('shortname' => 'fullage'))) {
                $DB->insert_record('user_info_field', $userfield);
            }
        }

        upgrade_plugin_savepoint(true, 2017062100, 'local', 'ent_installer');
    }

    if ($oldversion < 2018060800) {

        $table = new xmldb_table('local_ent_installer');

        // Launch add field starttime.
        $field = new xmldb_field('synctype', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'user', 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('deleted', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'updated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('deleteerrors', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, 'updateerrors');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2018060800, 'local', 'ent_installer');
    }

    return true;
}
