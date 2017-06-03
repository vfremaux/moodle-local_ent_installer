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
 * Form for activating manual resync.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// This allows 2 minutes synchronisation before trigerring an overtime.
define('OVERTIME_THRESHOLD', 120);

/**
 * get strings from a special install file, whatever
 * moodle active language is on
 * @return the string or the marked key if missing
 *
 */
function ent_installer_string($stringkey) {
    global $CFG;
    static $installstrings = null;

    if (empty($installstrings)) {
        include_once($CFG->dirroot.'/local/ent_installer/db/install_strings.php');
        $installstrings = $string; // Loads string array once.
    }

    if (!array_key_exists($stringkey, $installstrings)) {
        return "[[install::$stringkey]]";
    }
    return $installstrings[$stringkey];
}

function local_ent_installer_generate_email($user) {
    global $CFG;

    $fullname = strtolower($user->firstname.'.'.$user->lastname);
    $fakedomain = get_config('local_ent_installer', 'fake_email_domain');

    if (empty($fakedomain)) {
        $fakedomain = 'foomail.com';
    }

    return $fullname.'@'.$fakedomain;
}
