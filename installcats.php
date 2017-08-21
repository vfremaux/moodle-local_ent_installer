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
 * An accessory script alowing installing or reinstalling the initial categories
 *
 * Implementation Specific : ATOS / ENT Atrium Paca, Toutatice ENT Bretagne
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');

$url = new moodle_url('/local/ent_installer/installcats.php');
$PAGE->set_url($url);

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$hdrstr = get_string('installcats', 'local_ent_installer');

$PAGE->set_context($systemcontext);
$PAGE->set_heading($hdrstr);
$PAGE->set_pagelayout('admin');

if (optional_param('confirm', false, PARAM_BOOL)) {
    local_ent_installer_install_categories(false);
    redirect(new moodle_url('/course/management.php'));
}

echo $OUTPUT->header();

echo $OUTPUT->heading($hdrstr);

echo "<pre>";
// Simulates and tells what will be done.
local_ent_installer_install_categories(true);
echo "</pre>";

$params = array('confirm' => true);
$buttonurl = new moodle_url('/local/ent_installer/installcats.php', $params);

echo '<center>';
echo $OUTPUT->single_button($buttonurl, get_string('doit', 'local_ent_installer'));
echo '</center>';

echo $OUTPUT->footer();
