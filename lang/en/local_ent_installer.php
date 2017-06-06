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

$string['ent_installer:sync'] = 'Can sync manually the users.';

$string['automatedcohortmembers'] = 'Automated cohort members';
$string['backtosettings'] = 'Back to settings';
$string['backtosite'] = 'Back to site';
$string['bycity'] = 'By City';
$string['byname'] = 'By Name';
$string['byid'] = 'By ID';
$string['cleaningautomatedcohortscontent'] = 'Cleaning {$a} automated cohorts content';
$string['configbuildteachercategory'] = 'Build teacher category';
$string['configbuildteachercategory_desc'] = 'If enabled, a teacher dedicated category will be built in the teachers workplaces category for each new teacher imported.';
$string['configcohortindex'] = 'Cohort index';
$string['configcohortindex_desc'] = 'This index will be prepended to all generated cohorts. You shall change this prefix when a course session switches to a new session.';
$string['configcohortsortprefixlength'] = 'Cohort sort prefix length';
$string['configcohortsortprefixlength_desc'] = 'Length of the cohort prefix that will be tested for sorting';
$string['configcronenable'] = 'Enable cron processing';
$string['configcronenable_desc'] = 'Enable processing users integration using Moodle cron. disable this option if you plan to cron yourself the user loading using CLI script.';
$string['configcrontime'] = 'Time for running cron';
$string['configfakemaildomain'] = 'autogenerated mail domain';
$string['configfakemaildomain_desc'] = ' all generated mails when mail is missing in incomming profiles will be on this domain';
$string['configgetid'] = 'Structure ID';
$string['configgetinstitutionidservice'] = 'Get a structure ID';
$string['configinstitutionid'] = 'Institution ID';
$string['configinstitutionid_desc'] = 'The Education system school id';
$string['configlastsyncdate'] = 'Last synchro';
$string['configlastsyncdate_desc'] = 'Last synchonisation date. If you change this, the next synchronisation will condsider all users changed or created since this date';
$string['configmaildisplay'] = 'Initial mail visibility';
$string['configmaildisplay_desc'] = 'The initial mail visibility profile setting for new synced users.';
$string['configrealauth'] = 'Real authentication';
$string['configrealauth_desc'] = 'This setting sets the real authenticaiton method that will assigned to imported users, whatever the auth scheme used for import (hardlinked to LDAP)';
$string['configstructurecontext'] = 'LDAP context';
$string['configstructurecontext_desc'] = 'LDAP context where to find structures';
$string['configstructureid'] = 'ID';
$string['configstructureid_desc'] = 'LDiff attribute holding the structure ID';
$string['configstructurename'] = 'Current name';
$string['configstructurename_desc'] = 'LDiff attribute holding the structure name';
$string['configstructurenamefilter'] = 'LDAP filter for current name';
$string['configstructurenamefilter_desc'] = 'Filter pattern for searching by name';
$string['configstructurecity'] = 'Structure city';
$string['configstructurecity_desc'] = 'LDiff attribute holding the structure city';
$string['configstructurecityfilter'] = 'LDAP filter for current city';
$string['configstructurecityfilter_desc'] = 'Filter pattern for searching by city';
$string['configteachermaskfirstname'] = 'Mask teacher firstname';
$string['configteachermaskfirstname_desc'] = 'If checked the first name will be reduced to initals in category name';
$string['configsyncenable'] = 'Enable';
$string['configsyncenable_desc'] = 'enables the synchronisation script (CLI). If disabled, the sync script will have no effect at all, even if lauched by cron.';
$string['configsyncusersenable'] = 'Enable user accounts';
$string['configsynccohortsenable'] = 'Enable cohorts';
$string['configsyncgroupsenable'] = 'Enable course groups';
$string['configsyncsystemrolesenable'] = 'Enable system roles attribution';
$string['configsyncenable_desc'] = 'enables the synchronisation script (CLI). If disabled, the sync script will have no effect at all, even if lauched by cron.';
$string['configteacherstubcategory'] = 'Teacher course stub category';
$string['configteacherstubcategory_desc'] = 'The category holding all teacher course stubs';
$string['configupdateinstitutionstructure'] = 'Update institution structure';
$string['configupdateinstitutionstructure_desc'] = 'If enabled, the academic structure of the institution will be updated.';
$string['configentuserinfoattributes'] = 'LDAP attribute list for full profile feeding';
$string['configentuserinfoattributes_desc'] = 'A coma separated list of attributes to retreive in profile';
$string['configcreatestudentssitecohort'] = 'Create the global site students cohort';
$string['configcreatestudentssitecohort_desc'] = '';
$string['configcreatestaffsitecohort'] = 'Create the global site staff cohort';
$string['configcreatestaffsitecohort_desc'] = '';
$string['configstudentssitecohortname'] = 'Name for the global student cohort';
$string['configstudentssitecohortname_desc'] = '';
$string['configstaffsitecohortname'] = 'Name for the global site staff cohort';
$string['configstaffsitecohortname_desc'] = '';
$string['configadminssitecohortname'] = 'Name for the global admins cohort';
$string['configadminssitecohortname_desc'] = '';
$string['configcreatecohortsfromuserrecords'] = 'Create cohorts from users';
$string['configcreatecohortsfromuserrecords_desc'] = 'Create cohorts from user records, using the standard ENT field ENTClasses. Do not use this option when the annuary can provide full groups/cohorts descriptions';
$string['datasync'] = 'ENT Data Synchronisation';
$string['defaultarchivecatname'] = 'Archives';
$string['datasyncsettings'] = 'ENT Data Synchronisation Settings';
$string['dbinsertuser'] = 'ALIM : User creation [$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['dbinsertusersimul'] = 'SIMUL : User creation [$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['dbupdateuser'] = 'ALIM : User update or complete {$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['dbupdateusersimul'] = 'SIMUL : User update or complete {$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['finaloperations'] = 'Final cleanup operations';
$string['force'] = 'Force updating all entries (even unmodified since last reference)';
$string['getinstitutionidservice'] = 'Structure ID Search';
$string['id'] = 'Structure Identifier';
$string['inserterrors'] = 'Insert errors';
$string['inserts'] = 'Inserts (users added)';
$string['lastrun'] = 'Last run on {$a}';
$string['lasttime'] = 'Last run time';
$string['maxduration'] = 'Max sync duration';
$string['meantime'] = 'Mean syncing time';
$string['mergesiteadmins'] = 'Update site admins to {$a}';
$string['minduration'] = 'Min sync duration';
$string['noresults'] = 'No results';
$string['normalmeantime'] = 'Normal mean (wo overtimes)';
$string['nousers'] = 'No users in cohort';
$string['onceaday'] = 'daily';
$string['onceamonth'] = 'monthly';
$string['onceaweek'] = 'weekly';
$string['overtime'] = 'Overtimes';
$string['overtimes'] = 'Overtimes (> {$a} secs)';
$string['pluginname'] = 'Installation Moodle ENT';
$string['reset'] = 'Reset stats data';
$string['roleassigns'] = 'Role assignments';
$string['resetallvnodes'] = 'Reset stats data in all nodes';
$string['revivingdeletedorsuspended'] = 'Reviving suspended or deleted users';
$string['runsync'] = 'Run synchro';
$string['search'] = 'Search';
$string['seedetail'] = '(See user list)';
$string['simulate'] = 'Simulate mode';
$string['structuresearch'] = 'Structure search settings';
$string['syncbench'] = 'Sync time measurement';
$string['syncbenchreport_desc'] = 'A <a href="{$a}">report about user synchronisation benching</a>';
$string['syncdisabled'] = 'ENT Data sync is disabled on this site';
$string['syncusersdisabled'] = 'ENT Users sync is disabled on this site';
$string['synccohortsdisabled'] = 'ENT Cohorts sync is disabled on this site';
$string['syncroleassignsdisabled'] = 'ENT Role Assignments sync is disabled on this site';
$string['syncgroupsdisabled'] = 'ENT Course Groups sync is disabled on this site';
$string['synchroniseusers'] = 'Synchronize users';
$string['synctime'] = 'Sync time';
$string['synctimes'] = 'Sync times';
$string['synctimetitle'] = 'User Sync Time Measurement';
$string['syncusers'] = 'Synchro';
$string['syncusers_desc'] = '<a href="{$a}">Launch a manual synchronisation</a>';
$string['updateerrors'] = 'Updates errors';
$string['updates'] = 'Updates (users updated)';
$string['updatingusers'] = 'Updating user (attributes only)';
$string['usersdeletion'] = 'Users deletion';
$string['wsallhosts'] = 'Fetch for all hosts (vmoodle)';
$string['wsdateformat'] = 'Date format';
$string['wsentsyncdate'] = 'Users sync last date';
$string['wslastcron'] = 'Site known last cron time';
$string['wssetting'] = 'Setting to monitor';
$string['teachercatreorder'] = 'Reorder teacher categories';
$string['verbose'] = 'Verbose mode';
$string['users'] = 'User accounts';
$string['cohorts'] = 'Cohorts';
$string['coursegroups'] = 'Course groups';
$string['module'] = 'Course module';
$string['options'] = 'Processing options';
$string['entities'] = 'Entities';

$string['personfilters'] = 'LDAP Filters for user profiles';
$string['configstudentusertypefilter'] = 'Student user discriminator';
$string['configstudentusertypefilter_desc'] = '';
$string['configstudentinstitutionfilter'] = 'Student institution discriminator';
$string['configstudentinstitutionfilter_desc'] = '';
$string['configteachstaffusertypefilter'] = 'Teacher user discriminator';
$string['configteachstaffusertypefilter_desc'] = '';
$string['configteachstaffinstitutionfilter'] = 'Teacher institution discriminator';
$string['configteachstaffinstitutionfilter_desc'] = '';
$string['configadminstaffusertypefilter'] = 'Administrative user discriminator';
$string['configadminstaffusertypefilter_desc'] = '';
$string['configadminstaffinstitutionfilter'] = 'Administrative institution discriminator';
$string['configadminstaffinstitutionfilter_desc'] = '';
$string['configsiteadminsinstitutionfilter'] = 'Site admins institution discriminator';
$string['configsiteadminsinstitutionfilter_desc'] = '';
$string['configsiteadminsusertypefilter'] = 'Site administrators discriminator';
$string['configsiteadminsusertypefilter_desc'] = '';
$string['configstudentcohortuserfield'] = 'User cohort field';
$string['configstudentcohortuserfield_desc'] = '';
$string['configstudentcohortuserfieldfilter'] = 'User cohort field extractor';
$string['configstudentcohortuserfieldfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configstudenttransportuserfield'] = 'User transport field';
$string['configstudenttransportuserfield_desc'] = 'Ldap field telling the transportation mode of the user';
$string['configstudenttransportuserfieldfilter'] = 'User transport field extractor';
$string['configstudenttransportuserfieldfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configstudentregimeuserfield'] = 'User regime field';
$string['configstudentregimeuserfield_desc'] = 'Ldap field telling the regime of the user';
$string['configstudentregimeuserfieldfilter'] = 'User regime field extractor';
$string['configstudentregimeuserfieldfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configstudentfullageuserfield'] = 'User Full Age field';
$string['configstudentfullageuserfield_desc'] = 'Ldap field telling if the user is full legal age';
$string['configstudentfullageuserfieldfilter'] = 'User full age field extractor';
$string['configstudentfullageuserfieldfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';

// Cohorts

$string['cohortsfilters'] = 'Cohort LDAP filters';

$string['configcohortcontexts'] = 'Cohort contexts';
$string['configcohortcontexts_desc'] = 'Contexts where to find cohort groups. Several contexts can be given separated with ;';
$string['configcohortobjectclass'] = 'Cohort object classes filter';
$string['configcohortobjectclass_desc'] = 'Object class that are legitimate for cohort description';
$string['configcohortidattribute'] = 'Cohort id (ldapside)';
$string['configcohortidattribute_desc'] = 'Attribute for searching one cohort record by id';
$string['configcohortselectorfilter'] = 'Cohort selection filter';
$string['configcohortselectorfilter_desc'] = 'A LDAP filter for getting cohort identifiers. Accepts a %ID% placeholder for the institution ID. If several institution ids are associated, the filter will play for each ID.';
$string['configcohortidpattern'] = 'Cohort id pattern';
$string['configcohortidpattern_desc'] = 'A replaceable pattern for building the external cohort id from internal info. Accepts %ID% (institution id= and %CID% (internal cohort id) replacements';
$string['configcohortidnumberattribute'] = 'Attribute for cohort IDNumber';
$string['configcohortidnumberattribute_desc'] = 'Ldap field for the cohort IDNumber';
$string['configcohortidnumberfilter'] = 'Filter to extract cohort IDnumber';
$string['configcohortidnumberfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configcohortnameattribute'] = 'Attribute for cohort name';
$string['configcohortnameattribute_desc'] = 'Ldap field for the cohort name';
$string['configcohortnamefilter'] = 'Filter to extract cohort name';
$string['configcohortnamefilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configcohortdescriptionattribute'] = 'Attribute for description';
$string['configcohortdescriptionattribute_desc'] = 'Ldap field for the cohort description';
$string['configcohortmembershipattribute'] = 'Attribute for membership';
$string['configcohortmembershipattribute_desc'] = 'Ldap field for finding users in cohort';
$string['configcohortmembershipfilter'] = 'Filter for membership';
$string['configcohortmembershipfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configcohortuseridentifier'] = 'Internal cohort user identifier';
$string['configcohortuseridentifier_desc'] = '';
$string['id'] = 'Primary id';

$string['deletingcohorts'] = 'Deleting old cohorts';
$string['creatingcohorts'] = 'Creating new cohorts';
$string['updatingcohorts'] = 'Updating existing cohorts';
$string['cohortdeleted'] = 'Cohort {$a} deleted';
$string['cohortcreated'] = 'Cohort {$a} created';
$string['cohortupdated'] = 'Cohort {$a-name} updated. Idnumber set to {$a->idnumber}';
$string['cohortmemberadded'] = 'Cohort member {$a->username} added to cohort {$a->idnumber}';
$string['cohortmemberremoved'] = 'Cohort member {$a->username} removed from cohort {$a->idnumber}';
$string['disableautocohortscheck'] = 'Disable autocohort check';

// Role assignments.

$string['roleassignsfilters'] = 'Role assignments LDAP filters';

$string['configsyncroleassignsenable'] = 'Enables role assigns synchronisation';
$string['configsyncroleassignsenable_desc'] = '';
$string['configroleassigncontexts'] = 'Role assignments contexts';
$string['configroleassigncontexts_desc'] = 'Contexts where to find role assignment sets. Several contexts can be given separated with ;';
$string['configroleassignobjectclass'] = 'Role assignment object classes filter';
$string['configroleassignobjectclass_desc'] = 'Object class that are legitimate for role assignment description';
$string['configroleassignidattribute'] = 'Role assignment id';
$string['configroleassignidattribute_desc'] = 'LDAP Attribute for searching one role assignment record by id. Usually the DN.';
$string['configroleassignselectorfilter'] = 'Role assignment selection filter';
$string['configroleassignselectorfilter_desc'] = 'A LDAP filter for getting role assignment entries. Accepts a %ID% placeholder for the institution ID. If several institution ids are associated, the filter will play for each ID.';
$string['configroleassignroleattribute'] = 'Attribute for roleassign IDNumber';
$string['configroleassignroleattribute_desc'] = 'LDAP attribute for the roleassign IDNumber';
$string['configroleassignrolefilter'] = 'Filter to extract roleassign IDnumber';
$string['configroleassignrolefilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configroleassignrolemapping'] = 'Mapping table to transcode role names from LDAP to Moodle';
$string['configroleassignrolemapping_desc'] = 'A mapping table given as a list of pairs, one per line, in the "in => out" format. Leading and trailing spaces are trimmed.';
$string['configroleassigncontextlevelattribute'] = 'Attribute for role assignment contextlevel';
$string['configroleassigncontextlevelattribute_desc'] = 'LDAP attribute containing an information about context level';
$string['configroleassigncontextlevelfilter'] = 'Filter to extract context level key';
$string['configroleassigncontextlevelfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configroleassigncontextlevelmapping'] = 'Mapping table to transcode context levels from LDAP to Moodle';
$string['configroleassigncontextlevelmapping_desc'] = 'A mapping table given as a list of pairs, one per line, in the "in => out" format. Leading and trailing spaces are trimmed.';
$string['configroleassigncontextattribute'] = 'Attribute for the role assignment context';
$string['configroleassigncontextattribute_desc'] = 'LDAP attribute for the role assignment target context key.';
$string['configroleassigncontextfilter'] = 'Filter to extract the context key';
$string['configroleassigncontextfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configroleassigncoursecatkey'] = 'Course category key';
$string['configroleassigncoursecatkey_desc'] = 'Moodle field for finding course categories.';
$string['configroleassigncoursekey'] = 'Course key';
$string['configroleassigncoursekey_desc'] = 'Moodle field for finding a course.';
$string['configroleassignmodulekey'] = 'Course module key';
$string['configroleassignmodulekey_desc'] = 'Moodle field for finding a course module.';
$string['configroleassignblockkey'] = 'Block key';
$string['configroleassignblockkey_desc'] = 'Moodle field for finding a block.';
$string['configroleassigntargetuserkey'] = 'Target user key';
$string['configroleassigntargetuserkey_desc'] = 'Moodle field for finding the target user context.';
$string['configroleassignuserkey'] = 'Assigned user key';
$string['configroleassignuserkey_desc'] = 'Moodle field that identifies user for assignment';
$string['configroleassignmembershipattribute'] = 'Attribute for assigned users';
$string['configroleassignmembershipattribute_desc'] = 'Ldap field for finding users in role assignment';
$string['configroleassignmembershipfilter'] = 'Filter for membership';
$string['configroleassignmembershipfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';

// Groups.

$string['groupsfilters'] = 'group LDAP filters';

$string['configgroupcontexts'] = 'Group contexts';
$string['configgroupcontexts_desc'] = 'Contexts where to find group groups. Several contexts can be given separated with ;';
$string['configgroupautonameprefix'] = 'Group auto name prefix';
$string['configgroupautonameprefix_desc'] = 'A prefix applied to group names when created automatically';
$string['configgroupobjectclass'] = 'group object classes filter';
$string['configgroupobjectclass_desc'] = 'Object class that are legitimate for group description';
$string['configgroupidattribute'] = 'group id (ldapside)';
$string['configgroupidattribute_desc'] = 'Attribute for searching one group record by id';
$string['configgroupselectorfilter'] = 'group selection filter';
$string['configgroupselectorfilter_desc'] = 'A LDAP filter for getting group identifiers. Accepts a %ID% placeholder for the institution ID. If several institution ids are associated, the filter will play for each ID.';
$string['configgroupidpattern'] = 'group id pattern';
$string['configgroupidpattern_desc'] = 'A replaceable pattern for building the external group id from internal info. Accepts %ID% (institution id= and %CID% (internal group id) replacements';
$string['configgroupidnumberattribute'] = 'Attribute for group IDNumber';
$string['configgroupidnumberattribute_desc'] = 'Ldap field for the group IDNumber';
$string['configgroupidnumberfilter'] = 'Filter to extract group IDnumber';
$string['configgroupidnumberfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configgroupgroupingattribute'] = 'Attribute for group grouping name';
$string['configgroupgroupingattribute_desc'] = 'Ldap field for finding the grouping identifier. this will be taken as grouping name. If empty, the grouping is NOT evaluated.';
$string['configgroupgroupingfilter'] = 'Filter to extract grouping name';
$string['configgroupgroupingfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configgroupnameattribute'] = 'Attribute for group name';
$string['configgroupnameattribute_desc'] = 'Ldap field for the group name';
$string['configgroupnamefilter'] = 'Filter to extract group name';
$string['configgroupnamefilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configgroupdescriptionattribute'] = 'Attribute for description';
$string['configgroupdescriptionattribute_desc'] = 'Ldap field for the group description';
$string['configgroupmembershipattribute'] = 'Attribute for membership';
$string['configgroupmembershipattribute_desc'] = 'Ldap field for finding users in group';
$string['configgroupmembershipfilter'] = 'Filter for membership';
$string['configgroupmembershipfilter_desc'] = 'A regex that applies to value and catches the first available subpattern';
$string['configgroupuseridentifier'] = 'Internal group user identifier';
$string['configgroupuseridentifier_desc'] = '';

$string['deletinggorups'] = 'Deleting old groups';
$string['creatinggroups'] = 'Creating new groups';
$string['updatinggroups'] = 'Updating existing groups';
$string['groupdeleted'] = 'Group {$a->name} deleted in course {$a->course}';
$string['groupcreated'] = 'Group {$a->name} created in course {$a->course}';
$string['groupupdated'] = 'Group {$a->name} updated in course {$a->course}';
$string['groupmemberadded'] = 'Group member {$a->username} added to group {$a->name} in course {$a->course}';
$string['groupmemberremoved'] = 'Group member {$a->username} removed from group {$a->name} in course {$a->course}';
$string['disableautogroupscheck'] = 'Disable auto group check';
