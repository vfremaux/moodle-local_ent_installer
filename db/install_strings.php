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

$string['academicinfocategoryname'] = 'Informations académiques';
$string['cohort'] = 'Classe';
$string['fullage'] = 'Etudiant majeur';
$string['isprimaryassignation'] = 'Est affectation principale';
$string['personaltitle'] = 'Civilité';
$string['regime'] = 'Regime';
$string['transport'] = 'Transport';
$string['usertypecategoryname'] = 'Type utilisateur';
$string['usertypeparent'] = 'Parents';
$string['usertypestaff'] = 'Administration';
$string['usertypestudent'] = 'Elève';
$string['usertypeteacher'] = 'Enseignant';
$string['usertypeworkmanager'] = 'chef de travaux';

$string['usertypestudent_desc'] = 'Elèves';

$string['usertypestaff_desc'] = 'Personnels administratifs et non enseignant';

$string['usertypeteacher_desc'] = 'Personnel enseignant';

$string['usertypeparent_desc'] = 'Parents et relatifs';

$string['usertypeworkmanager_desc'] = 'Chefs de travaux et assimilés';

$string['regime_desc'] = 'Regime de demi-pension. Provient du champ ENTEleveRegime du SDET.';

$string['fullage_desc'] = 'Marqueur de profil majorité';

$string['transport_desc'] = 'Utilise un moyen de transport. Provient du champ ENTEleveTransport du SDET.';

$string['personaltitle_desc'] = ' provient du champ personalTitle du schéma d\'annuaire ENT.';

$string['cohort_desc'] = 'Les classes sont utilisées pour générer des cohortes et proviennent du champ ENTEleveClasses du SDET';

$string['isprimaryassignation_desc'] = '
Est l\'établissement de rattachement définit par ENTPersonStructRattach du
schéma d\'annuaire ENT.
';
