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
 * settings for structure operations
 *
 * @package     local_ent_installer
 * @category    local
 * @copyright   2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_heading('head5', get_string('structuresearch', 'local_ent_installer'), ''));

$key = 'local_ent_installer/structure_context';
$label = get_string('configstructurecontext', 'local_ent_installer');
$desc = get_string('configstructurecontext_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_id_attribute';
$label = get_string('configstructureid', 'local_ent_installer');
$desc = get_string('configstructureid_desc', 'local_ent_installer');
$default = 'ENTStructureUAI';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_city_attribute';
$label = get_string('configstructurecity', 'local_ent_installer');
$desc = get_string('configstructurecity_desc', 'local_ent_installer');
$default = 'l';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_city_filter';
$label = get_string('configstructurecityfilter', 'local_ent_installer');
$desc = get_string('configstructurecityfilter_desc', 'local_ent_installer');
$default = '(&(objectClass=ENTEtablissement)(l=%SEARCH%))';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_name_attribute';
$label = get_string('configstructurename', 'local_ent_installer');
$desc = get_string('configstructurename_desc', 'local_ent_installer');
$default = 'ENTDisplayName';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_name_filter';
$label = get_string('configstructurenamefilter', 'local_ent_installer');
$desc = get_string('configstructurenamefilter_desc', 'local_ent_installer');
$default = '(&(objectClass=ENTEtablissement)(ENTDisplayName=%SEARCH%))';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_address_attribute';
$label = get_string('configstructureaddress', 'local_ent_installer');
$desc = get_string('configstructureaddress_desc', 'local_ent_installer');
$default = 'street';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/structure_geoloc_attribute';
$label = get_string('configstructuregeoloc', 'local_ent_installer');
$desc = get_string('configstructuregeoloc_desc', 'local_ent_installer');
$default = 'ENTStructureGeoLoc';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer_getid';
$label = get_string('configgetid', 'local_ent_installer');
$getidstr = get_string('configgetinstitutionidservice', 'local_ent_installer');
$html = '<a href="'.$CFG->wwwroot.'/local/ent_installer/getid.php"><input type="button" class="btn" value="'.$getidstr.'" /></a>';
$settings->add(new admin_setting_heading($key, $label, $html));

if (is_dir($CFG->dirroot.'/local/vmoodle')) {
    // Add provision to get some instance related metadata.
    if ($DB->record_exists('local_vmoodle', array('vhostname' => $CFG->wwwroot))) {
        $refreshsitemetadatastr = get_string('refreshsitemetadata', 'local_ent_installer');
        $html = '<a href="'.$CFG->wwwroot.'/local/ent_installer/refreshmetadata.php"><input type="button" class="btn" value="'.$refreshsitemetadatastr.'" /></a>';
    }
}
