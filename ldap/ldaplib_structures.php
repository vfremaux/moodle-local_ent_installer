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
 * LDAP and Sync operations about learning structures.
 *
 * @package     local_ent_installer
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2015 onwards Valery Fremaux (http://www.mylearnignfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * a utility function that explores the ldap ENTEtablissement object list to get proper institution id
 *
 * @param object $ldapauth the ldap authentication instance
 * @param string $search the search pattern
 * @param array $searchby where to search, either 'name' or 'city'
 * @return an array of objects with institution ID and institution name
 */
function local_ent_installer_ldap_search_institution_id($ldapauth, $search, $searchby = 'name') {

    $ldapconnection = $ldapauth->ldap_connect();

    $context = get_config('local_ent_installer', 'structure_context');
    $config = get_config('local_ent_installer');

    // Just for tests.
    if (empty($context)) {
        $context = 'ou=structures,dc=atrium-paca,dc=fr';
    }

    if ($searchby == 'name') {

        if ($search != '*') {
            $search = '*'.$search.'*';
        }

        $filter = str_replace('%SEARCH%', $search, $config->structure_name_filter);
    } else if ($searchby == 'city') {

        if ($search != '*') {
            $search = '*'.$search.'*';
        }

        $filter = str_replace('%SEARCH%', $search, $config->structure_city_filter);
    } else {
        // Search by id.
        $filter = '('.$config->structure_id_attribute.'='.$search.')';
    }

    $structureid = $config->structure_id_attribute;
    $structurename = $config->structure_name_attribute;
    $structurecity = $config->structure_city_attribute;
    $structureaddress = $config->structure_address_attribute;
    $structuregeoloc = $config->structure_geoloc_attribute;

    // Just for tests.
    if (empty($structurename)) {
        $structurename = 'ENTStructureNomCourant';
    }

    list($usec, $sec) = explode(' ',microtime());
    $pretick = (float)$sec + (float)$usec;

    // Search only in this context.
    echo "Searching in $context where $filter for ($structureid, $structurename, $structurecity, $structuregeoloc, $structureaddress) <br/>";
    $ldap_result = @ldap_search($ldapconnection, $context, $filter, array($structureid, $structurename, $structurecity, $structuregeoloc, $structureaddress));
    list($usec, $sec) = explode(' ',microtime()); 
    $posttick = (float)$sec + (float)$usec;

    if (!$ldap_result) {
        return '';
    }

    $results = array();
    if ($entry = @ldap_first_entry($ldapconnection, $ldap_result)) {
        do {
            $institution = new StdClass();

            $value = ldap_get_values_len($ldapconnection, $entry, $structureid);
            $institution->id = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $value = ldap_get_values_len($ldapconnection, $entry, $structurename);
            $institution->name = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $value = ldap_get_values_len($ldapconnection, $entry, $structurecity);
            $institution->city = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $value = ldap_get_values_len($ldapconnection, $entry, $structureaddress);
            $institution->address = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $value = ldap_get_values_len($ldapconnection, $entry, $structuregeoloc);
            $institution->geoloc = core_text::convert($value[0], $ldapauth->config->ldapencoding, 'utf-8');

            $results[] = $institution;

        } while ($entry = ldap_next_entry($ldapconnection, $entry));
    }
    unset($ldap_result); // Free mem.

    return $results;
}
