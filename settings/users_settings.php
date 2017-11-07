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
 * Settings for user synchronisation
 *
 * @package     local_ent_installer
 * @category    local
 * @copyright   2015 Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading('head1', get_string('personfilters', 'local_ent_installer'), ''));

$key = 'local_ent_installer/sync_users_enable';
$label = get_string('configsyncusersenable', 'local_ent_installer');
$desc = '';
$default = 1;
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, $default));

$key = 'local_ent_installer/generic_usertype_filter';
$label = get_string('configgenericusertypefilter', 'local_ent_installer');
$desc = get_string('configgenericusertypefilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/generic_institution_filter';
$label = get_string('configgenericinstitutionfilter', 'local_ent_installer');
$desc = get_string('configgenericinstitutionfilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_usertype_filter';
$label = get_string('configstudentusertypefilter', 'local_ent_installer');
$desc = get_string('configstudentusertypefilter_desc', 'local_ent_installer');
$default = '(objectClass=ENTEleve)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_institution_filter';
$label = get_string('configstudentinstitutionfilter', 'local_ent_installer');
$desc = get_string('configstudentinstitutionfilter_desc', 'local_ent_installer');
$default = '(ENTEleveClasses=*=%ID%,*)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/teachstaff_usertype_filter';
$label = get_string('configteachstaffusertypefilter', 'local_ent_installer');
$desc = get_string('configteachstaffusertypefilter_desc', 'local_ent_installer');
$default = '(objectClass=ENTAuxEnseignant)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/teachstaff_institution_filter';
$label = get_string('configteachstaffinstitutionfilter', 'local_ent_installer');
$desc = get_string('configteachstaffinstitutionfilter_desc', 'local_ent_installer');
$default = '(ENTPersonFonctions=*=%ID%,*)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/adminstaff_usertype_filter';
$label = get_string('configadminstaffusertypefilter', 'local_ent_installer');
$desc = get_string('configadminstaffusertypefilter_desc', 'local_ent_installer');
$default = '(objectClass=ENTAuxNonEnsEtab)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/adminstaff_institution_filter';
$label = get_string('configadminstaffinstitutionfilter', 'local_ent_installer');
$desc = get_string('configadminstaffinstitutionfilter_desc', 'local_ent_installer');
$default = '(ENTPersonFonctions=*=%ID%,*)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/siteadmins_usertype_filter';
$label = get_string('configsiteadminsusertypefilter', 'local_ent_installer');
$desc = get_string('configsiteadminsusertypefilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/siteadmins_institution_filter';
$label = get_string('configsiteadminsinstitutionfilter', 'local_ent_installer');
$desc = get_string('configsiteadminsinstitutionfilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_cohort_userfield';
$label = get_string('configstudentcohortuserfield', 'local_ent_installer');
$desc = get_string('configstudentcohortuserfield_desc', 'local_ent_installer');
$default = 'ENTEleveClasses';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_cohort_userfield_filter';
$label = get_string('configstudentcohortuserfieldfilter', 'local_ent_installer');
$desc = get_string('configstudentcohortuserfieldfilter_desc', 'local_ent_installer');
$default = 'ENTEleveClasses';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_regime_userfield';
$label = get_string('configstudentregimeuserfield', 'local_ent_installer');
$desc = get_string('configstudentregimeuserfield_desc', 'local_ent_installer');
$default = 'ENTEleveRegime';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_regime_userfield_filter';
$label = get_string('configstudentregimeuserfieldfilter', 'local_ent_installer');
$desc = get_string('configstudentregimeuserfieldfilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_transport_userfield';
$label = get_string('configstudenttransportuserfield', 'local_ent_installer');
$desc = get_string('configstudenttransportuserfield_desc', 'local_ent_installer');
$default = 'ENTEleveTransport';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_transport_userfield_filter';
$label = get_string('configstudenttransportuserfieldfilter', 'local_ent_installer');
$desc = get_string('configstudenttransportuserfieldfilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_fullage_userfield';
$label = get_string('configstudentfullageuserfield', 'local_ent_installer');
$desc = get_string('configstudentfullageuserfield_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/student_fullage_userfield_filter';
$label = get_string('configstudentfullageuserfieldfilter', 'local_ent_installer');
$desc = get_string('configstudentfullageuserfieldfilter_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/user_picture_field';
$label = get_string('configuserpicturefield', 'local_ent_installer');
$desc = get_string('configuserpicturefield_desc', 'local_ent_installer');
$default = '';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/user_picture_filter';
$label = get_string('configuserpicturefilter', 'local_ent_installer');
$desc = get_string('configuserpicturefilter_desc', 'local_ent_installer');
$default = '(.*)';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/user_picture_url_pattern';
$label = get_string('configuserpictureurlpattern', 'local_ent_installer');
$desc = get_string('configuserpictureurlpattern_desc', 'local_ent_installer');
$default = '%PICTURE%';
$settings->add(new admin_setting_configtext($key, $label, $desc, $default));

$key = 'local_ent_installer/ent_userinfo_attributes';
$label = get_string('configentuserinfoattributes', 'local_ent_installer');
$desc = get_string('configentuserinfoattributes_desc', 'local_ent_installer');
$default = 'ENTPersonFonctions,ENTPersonJointure,personalTitle,ENTPersonStructRattach,ENTEleveClasses';
$default .= 'ENTEleveTransport,ENTEleveRegime,ENTEleveMajeur,ENTPersonDateSuppression,seeAlso';
$settings->add(new admin_setting_configtextarea($key, $label, $desc, $default));
