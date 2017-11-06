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
 * External Ent Installer
 *
 * @package     local_ent_installer
 * @category    local
 * @copyright   2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class local_ent_installer_external extends external_api {

    /**
     * Describes the parameters for check_sync_dates.
     *
     * @return external_external_function_parameters
     * @since Moodle 2.5
     */
    public static function check_runtime_dates_parameters() {
        return new external_function_parameters (
            array(
                'setting' => new external_value(PARAM_TEXT, 'Date setting to monitor',
                        VALUE_REQUIRED, 'sync', NULL_NOT_ALLOWED),
                'allhosts' => new external_value(PARAM_INT, 'Switch to get all vmoodle values',
                        false, 0, NULL_NOT_ALLOWED),
                'dateformat' => new external_value(PARAM_INT, 'Format for dates',
                        false, 0, NULL_NOT_ALLOWED),
            )
        );
    }

    /**
     * Returns a list of runtime date values. If all hosts is not asked for, will return the current host
     * value as unique value.
     * @param array $courseids the course ids
     * @return array the forum details
     * @since Moodle 2.5
     */
    public static function check_runtime_dates($setting, $allhosts = false, $dateformat = 1) {
        global $CFG, $DB;

        $parameters = array('setting' => $setting, 'allhosts' => $allhosts, 'dateformat' => $dateformat);
        $params = self::validate_parameters(self::check_runtime_dates_parameters(), $parameters);

        $arrdates = array();
        if (empty($params['allhosts'])) {

            if ($params['setting'] == 'sync') {
                $settingvalue = get_config('local_ent_installer', 'last_sync_date');
            } else {
                $settingvalue = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
            }

            $arrdates[0] = array();
            $arrdates[0]['wwwroot'] = $CFG->wwwroot;
            $arrdates[0]['lastruntimedate'] = self::format_date($settingvalue, $dateformat);
        } else {
            if (is_dir($CFG->dirroot.'/local/vmoodle')) {
                $fields = 'id,name,vhostname,vdbhost,vdbname,vdbprefix';
                $vmoodles = $DB->get_records('local_vmoodle', array('enabled' => 1), 'name', $fields);

                foreach ($vmoodles as $vm) {
                    if ($vm->vdbhost == $CFG->dbhost) {
                        // Databases on same host so use direct query.
                        // Note we DO NOT use moodle query format as using a cross base extension.
                        $result = false;
                        if ($params['setting'] == 'sync') {
                            $sql = "
                                SELECT
                                    value
                                FROM
                                    `".$vm->vdbname."`.`".$vm->vdbprefix."config_plugins`
                                WHERE
                                    plugin = 'local_ent_installer' AND
                                    name = 'last_sync_date'
                            ";
                            $result = $DB->get_field_sql($sql);
                        } else {
                            $sql = "
                                SELECT
                                    MAX(lastcron)
                                FROM
                                    `".$vm->vdbname."`.`".$vm->vdbprefix."modules`
                            ";
                            $result = $DB->get_field_sql($sql);
                        }
                        if ($result !== false) {
                            $resultarr = array();
                            $resultarr['wwwroot'] = $vm->vhostname;
                            $resultarr['lastruntimedate'] = self::format_date($result, $dateformat);
                            $arrdates[] = $resultarr;
                        } else {
                            $resultarr = array();
                            $resultarr['wwwroot'] = $vm->vhostname.'#error';
                            $resultarr['lastruntimedate'] = 0;
                            $arrdates[] = $resultarr;
                        }
                    } else {
                        /*
                         * TODO : implement a mnet call using vmoodle generic config fetch function.
                         * this was not needed yet
                         */
                         assert(1);
                    }
                }
            } else {
                $resultarr = array();
                $resultarr['wwwroot'] = 'VMoodle Not implemented';
                $resultarr['lastruntimedate'] = 0;
                $arrdates[] = $resultarr;
            }
        }

        return $arrdates;
    }

    /**
     * Describes the get_forum return value.
     *
     * @return external_single_structure
     * @since Moodle 2.5
     */
    public static function check_runtime_dates_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'wwwroot' => new external_value(PARAM_URL, 'Host base url'),
                    'lastruntimedate' => new external_value(PARAM_TEXT, 'Last runtime date for the required setting'),
                ), 'Host records'
            )
        );
    }

    /**
     * Local date formatting function.
     * @param int $datevalue a unix timestamp
     * @param string $dateformat
     */
    private static function format_date($datevalue, $dateformat) {
        switch ($dateformat) {
            case 1:
                return date('Y-m-d H:i:s', $datevalue);

            case 2:
                return date('d/m/Y H:i:s', $datevalue);

            case 3:
                return userdate($datevalue);

            default:
                return $datevalue;
        }
    }
}
