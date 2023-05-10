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
 * @author Valery Fremaux valery.fremaux@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package report_zabbix
 * @category report
 */
namespace report_zabbix\indicators;

use moodle_exception;
use coding_exception;
use StdClass;

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');
require_once($CFG->dirroot.'/local/shop/locallib.php');

class daily_ent_installer_indicator extends zabbix_indicator {

    static $submodes = 'lastusersyncdate,lateusersync,usersyncstate,coursesyncstate,cohortsyncstate,groupsyncstate,roleassignsyncstate';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.ent_installer';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        return explode(',', self::$submodes);
    }

    /**
     * the function that contains the logic to acquire the indicator instant value.
     * @param string $submode to target an aquisition to an explicit submode, elsewhere 
     */
    public function acquire_submode($submode) {
        global $DB, $CFG;

        if(!isset($this->value)) {
            $this->value = new Stdclass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        $now = time();
        $horizon = $now - DAYSECS;
        $config = get_config('local_ent_installer');

        switch ($submode) {

            case 'lastusersyncdate': {
                $this->value->$submode = $config->last_user_sync_date;
                break;
            }

            case 'lateusersync': {
                $this->value->$submode = ($config->last_user_sync_date > time() - DAYSECS * 3) ? 1 : 0 ;
                break;
            }

            case 'usersyncstate': {
                $this->value->$submode = $config->sync_users_enable;
                break;
            }

            case 'coursesyncstate': {
                $this->value->$submode = $config->sync_courses_enable;
                break;
            }

            case 'groupsyncstate': {
                $this->value->$submode = $config->sync_groups_enable;
                break;
            }

            case 'cohortsyncstate': {
                $this->value->$submode = $config->sync_cohorts_enable;
                break;
            }

            case 'roleassignsyncstate': {
                $this->value->$submode = $config->sync_roleassigns_enable;
                break;
            }

            default: {
                if ($CFG->debug == DEBUG_DEVELOPER) {
                    throw new coding_exception("Indicator has a submode that is not handled in aquire_submode().");
                }
            }
        }
    }
}