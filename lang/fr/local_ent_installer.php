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

$string['ent_installer:sync'] = 'Peut synchroniser manuellement les comptes utilisateur.';

$string['automatedcohortmembers'] = 'Membres de la cohorte automatique';
$string['backtosettings'] = 'Retour aux réglages';
$string['backtosite'] = 'Retour au site';
$string['bycity'] = 'Par ville';
$string['byname'] = 'Par nom';
$string['byid'] = 'Par RNE';
$string['cleaningautomatedcohortscontent'] = 'Nettoyage des {$a} alimentations de cohortes automatiques.';
$string['configbuildteachercategory'] = 'Contruire la catégorie enseignant';
$string['configbuildteachercategory_desc'] = 'Si actif, tout nouvel enseignant importé se verra attribuer une catégorie de cours à son nom dont il sera gestionnaire dans l\'espace pédagogique des enseignants.';
$string['configcohortindex'] = 'Préfixe de cohorte';
$string['configcohortindex_desc'] = 'Ce préfixe est ajouté aux noms de cohortes générées. Ce prefixe devrait être changé lors d\'un changement de session pédagogique (année scolaire) pour générer et maintenir un nouveau jeu de cohortes pour la session.';
$string['configcohortsortprefixlength'] = 'Longueur du préfixe de tri des cohortes';
$string['configcohortsortprefixlength_desc'] = '';
$string['configcronenable'] = 'Intégration par le cron';
$string['configcronenable_desc'] = 'Activez cette option si vous voulez laisser effectuer l\'intégration des utilisateurs par le cron Moodle. Désactivez cette option si vous prévoyez de planifier ce traitement par vous-même (mode CLI).';
$string['configcrontime'] = 'Heure de traitement';
$string['configfakemaildomain'] = 'Domaine des mails autogénérés ';
$string['configfakemaildomain_desc'] = ' Domaine utilisé pour générer des adresses mail factices lorsqu\'elles sont manquantes dans les profils importés';
$string['configgetid'] = 'ID de structure';
$string['configgetinstitutionidservice'] = 'Chercher un ID d\'établissement';
$string['configinstitutionid'] = 'ID Etablissement';
$string['configinstitutionid_desc'] = 'L\'identifiant de jointure d\'établissement Education Nationale';
$string['configlastsyncdate'] = 'Dernière synchro';
$string['configlastsyncdate_desc'] = 'Dernière date de synchro. Si vous changez cette date, la prochaine synchro considèrera tous les utilisateur créés ou modifiés à partir de cette date.';
$string['configmaildisplay'] = 'Visibilité des adresses courriel initiales';
$string['configmaildisplay_desc'] = 'La visibilité initiale des adresses courriel pour les utilisateurs nouvellement créés.';
$string['configrealauth'] = 'Méthode d\'authentification effective';
$string['configrealauth_desc'] = 'Ce réglage définit la méthode d\'authentification à attribuer aux comptes synchronisés de l\'ENT, indépendamment du plugin utilisé pour contacter l\'annuaire.';
$string['configstructurecontext'] = 'Contexte LDAP';
$string['configstructurecontext_desc'] = 'contexte(base DN) LDAP où sont stockées les définitions d\'établissement';
$string['configstructureid'] = 'Identifiant';
$string['configstructureid_desc'] = 'L\'attribut portant l\'dentifiant unique d\'établissement';
$string['configstructurename'] = 'Nom courant';
$string['configstructurename_desc'] = 'L\'attribut portant le nom courant de la structure';
$string['configstructurenamefilter'] = 'Filtre LDAP pour le nom';
$string['configstructurenamefilter_desc'] = 'Clause LDAP pour recherche dans le nom de l\'tablissement';
$string['configstructurecity'] = 'Ville';
$string['configstructurecity_desc'] = 'Attribut LDAP de la ville';
$string['configstructurecityfilter'] = 'Filtre LDAP pour la ville';
$string['configstructurecityfilter_desc'] = 'Clause LDAP pour recherche dans le nom de l\'tablissement';
$string['configsyncenable'] = 'Actif';
$string['configsyncenable_desc'] = 'Active la synchronisation régulière des données ENT (CLI). Si désactivé, le script de synchronisation n\'aura aucun effet même s\'il est lancé par cron.';
$string['configsyncusersenable'] = 'Activer les comptes utilisateur';
$string['configsynccohortsenable'] = 'Activer les cohortes';
$string['configsyncgroupsenable'] = 'Activer les groupes de cours';
$string['configsyncsystemrolesenable'] = 'Activer l\'attribution de roles système';
$string['configteacherstubcategory'] = 'Container espaces enseignants';
$string['configteacherstubcategory_desc'] = 'La catégorie contenant les containers de cours propres aux enseignants';
$string['configteachermaskfirstname'] = 'Masquer le prénom des enseignants';
$string['configteachermaskfirstname_desc'] = 'Si actif, le prénom des enseignants sera réduit aux initiales dans les catégories propres des enseignants';
$string['configupdateinstitutionstructure'] = 'Mettre à jour la structure établissement';
$string['configupdateinstitutionstructure_desc'] = 'Si actif, la structure de l\'établissement (catégories de classes) est mise à jour avant chaque synchronisation.';
$string['configentuserinfoattributes'] = 'Liste d\'attributs LDAP à récupérer pour l\'alimentation complète du profil';
$string['configentuserinfoattributes_desc'] = 'Une liste de noms d\'attributs séparée par des virgules (sans espaces)';
$string['configcreatestudentssitecohort'] = 'Créer la cohorte de site des élèves';
$string['configcreatestudentssitecohort_desc'] = '';
$string['configcreatestaffsitecohort'] = 'Créer la cohorte de site des enseignants';
$string['configcreatestaffsitecohort_desc'] = '';
$string['configstudentssitecohortname'] = 'Nom de la cohorte de site des élèves';
$string['configstudentssitecohortname_desc'] = '';
$string['configstaffsitecohortname'] = 'Nom de la cohorte de site des enseignants';
$string['configstaffsitecohortname_desc'] = '';
$string['configadminssitecohortname'] = 'Nom de la cohorte de site des administrateurs';
$string['configadminssitecohortname_desc'] = '';
$string['configcreatecohortsfromuserrecords'] = 'Creer les cohortes à partir des entrées utilisateur';
$string['configcreatecohortsfromuserrecords_desc'] = 'Si actif, crée les cohortes à partir des fiches d\'utilisateur à partir du champ ENTClasses. Désactivez cette options lorsque l\'annuaire contient des définitions complètes des groupes et que la synchro des cohortes est active.';
$string['datasync'] = 'Synchronisation de données ENT';
$string['datasyncsettings'] = 'Réglages de la synchronisation de données ENT';
$string['defaultarchivecatname'] = 'Corbeille';
$string['dbinsertuser'] = 'ALIMENTATION : Création utilisateur {$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['dbinsertusersimul'] = 'SIMULATION : Création utilisateur {$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['dbupdateuser'] = 'ALIMENTATION : Mise à jour utilisateur {$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['dbupdateusersimul'] = 'SIMULATION : Mise à jour utilisateur {$a->username} - {$a->idnumber}] role : {$a->usertype} / {$a->function}';
$string['finaloperations'] = 'Opérations finales';
$string['force'] = 'Forcer la mise à jour de toutes les entrées (y compris celles non modifiées dans la période de référence)';
$string['getinstitutionidservice'] = 'Recherche d\'identifiants d\'établissements';
$string['id'] = 'Identifiant RNE';
$string['inserterrors'] = 'Erreurs d\'insertion';
$string['inserts'] = 'Insertions (utilisateurs ajoutés)';
$string['lastrun'] = 'Dernière exécution {$a}';
$string['lasttime'] == 'Dernier passage';
$string['maxduration'] = 'Durée max';
$string['meantime'] = 'Moyenne';
$string['mergesiteadmins'] = 'Mise à jour des administrateurs de site à {$a}';
$string['minduration'] = 'Durée min';
$string['noresults'] = 'Aucun résultat';
$string['normalmeantime'] = 'Moyenne usuelle (sans dépassements)';
$string['nousers'] = 'Aucun utilisateur dans la cohorte';
$string['overtime'] = 'Dépassements';
$string['overtimes'] = 'Dépassement de temps critique (> {$a} secs)';
$string['pluginname'] = 'Installation spécifique Moodle ENT';
$string['reset'] = 'Mettre à zéro les statistiques';
$string['resetallvnodes'] = 'Mettre à zéro toutes les statistiques';
$string['revivingdeletedorsuspended'] = 'Réactiver des utilisateurs suspendus ou supprimés';
$string['runsync'] = 'Lancer la synchronisation';
$string['search'] = 'Recherche';
$string['seedetail'] = '(Voir la liste)';
$string['simulate'] = 'Mode simulation';
$string['structuresearch'] = 'Paramètres pour la recherche de structures';
$string['syncbench'] = 'Mesure des temps de synchronisation';
$string['syncbenchreport_desc'] = 'Un <a href="{$a}">rapport sur les temps de synchronisation</a>';
$string['syncdisabled'] = 'La synchro est désactivée sur ce site';
$string['synchroniseusers'] = 'Synchroniser les utilisateurs';
$string['synctime'] = 'Temps de syncro';
$string['synctimes'] = 'Temps de synchro';
$string['synctimetitle'] = 'Mesure des temps de chargement/mise à jour utilisateurs';
$string['syncusers'] = 'Synchro';
$string['syncusers_desc'] = '<a href="{$a}">Lancer une synchronisation manuelle</a>';
$string['updateerrors'] = 'Erreur de mises à jour';
$string['updates'] = 'Mises à jour (utilisateurs modifiés)';
$string['updatingusers'] = 'Mise à jour des utilisateurs (attributs seulement)';
$string['usersdeletion'] = 'Suppression des utilisateurs';
$string['wsallhosts'] = 'Récupérer pour tous les hôtes (vmoodle)';
$string['wsdateformat'] = 'Format de date';
$string['wsentsyncdate'] = 'Dernière synchronisation des utilisateurs';
$string['wslastcron'] = 'Dernier cron connu du site';
$string['wssetting'] = 'Date à observer';
$string['teachercatreorder'] = 'Réordonner les catégories enseignants';
$string['verbose'] = 'Plus de sorties de trace';
$string['users'] = 'Comptes utilisateur';
$string['cohorts'] = 'Cohortes';
$string['coursegroups'] = 'Groupes des cours';
$string['options'] = 'Options de traitement';
$string['entities'] = 'Entités';

$string['personfilters'] = 'Filtres LDAP pour les comptes utilisateur';
$string['configstudentusertypefilter'] = 'Discriminant des élèves';
$string['configstudentusertypefilter_desc'] = '';
$string['configstudentinstitutionfilter'] = 'Sélecteur d\'établissement des élèves';
$string['configstudentinstitutionfilter_desc'] = '';
$string['configteachstaffusertypefilter'] = 'Discriminant des personnels enseignants';
$string['configteachstaffusertypefilter_desc'] = '';
$string['configteachstaffinstitutionfilter'] = 'Sélecteur d\'établissements des personnels enseignants';
$string['configteachstaffinstitutionfilter_desc'] = '';
$string['configadminstaffusertypefilter'] = 'Discriminant des personnels non enseignants';
$string['configadminstaffusertypefilter_desc'] = '';
$string['configadminstaffinstitutionfilter'] = 'Sélecteur d\'établissements des personnels non enseignants';
$string['configadminstaffinstitutionfilter_desc'] = '';
$string['configsiteadminsinstitutionfilter'] = 'Sélecteur d\'établissements des administrateurs de site';
$string['configsiteadminsinstitutionfilter_desc'] = '';
$string['configsiteadminsusertypefilter'] = 'Sélecteur des administrateurs de site';
$string['configsiteadminsusertypefilter_desc'] = '';
$string['configstudentcohortuserfield'] = 'Champ des classes (cohortes)';
$string['configstudentcohortuserfield_desc'] = '';
$string['configstudentcohortuserfieldfilter'] = 'Extracteur de classe';
$string['configstudentcohortuserfieldfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configstudenttransportuserfield'] = 'Champ du mode de transport';
$string['configstudenttransportuserfield_desc'] = 'Champ indiquant le mode de tranport de l\'élève';
$string['configstudenttransportuserfieldfilter'] = 'Extracteur de transport';
$string['configstudenttransportuserfieldfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configstudentregimeuserfield'] = 'champ du régime';
$string['configstudentregimeuserfield_desc'] = 'Champ indiquant le régime d\'hébergement de l\'élève';
$string['configstudentregimeuserfieldfilter'] = 'Extracteur de régime';
$string['configstudentregimeuserfieldfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configstudentfullageuserfield'] = 'Champ d\'élève majeur';
$string['configstudentfullageuserfield_desc'] = 'Champ indiquant si l\'élève est majeur';
$string['configstudentfullageuserfieldfilter'] = 'Extracteur de majorité';
$string['configstudentfullageuserfieldfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';

// Cohortes

$string['cohortsfilters'] = 'Filtres LDAP pour les cohortes';
$string['configcohortcontexts'] = 'Contextes des cohortes';
$string['configcohortcontexts_desc'] = 'Contextes pour trouver les groupes de cohortes. Plusieurs contextes possibles séparés par des ;';
$string['configcohortobjectclass'] = 'Classes d\'objets des cohortes';
$string['configcohortobjectclass_desc'] = 'Filtre en syntaxe LDAP pour restreindre les classes d\'objets ldap examinées.';
$string['configcohortselectorfilter'] = 'Filtre de sélection des cohortes';
$string['configcohortselectorfilter_desc'] = 'Filtre en syntaxe LDAP pour extraire les identifiants de cohortes. Admet un remplacement %ID% pour le Rne. Si plusieurs Rne sont associés à l\'établissement, le filtre sera activé pour chaque établissement';
$string['configcohortidattribute'] = 'Attribut identifiant LDAP de cohorte';
$string['configcohortidattribute_desc'] = 'Attribut pour rechercher un enregistrement unique de cohorte';
$string['configcohortidpattern'] = 'Motif d\'identifant de cohorte';
$string['configcohortidpattern_desc'] = 'Un motif à remplacements pour construire la valeur de l\'identifiant. Accepte les emplacements %ID% (Rne) et %CID% (identifiant interne de cohorte).';
$string['configcohortidnumberattribute'] = 'Attribut du numéro d\'identification';
$string['configcohortidnumberattribute_desc'] = 'Attribut LDAP pour extraire le numéro d\'identification de cohorte';
$string['configcohortidnumberfilter'] = 'Filtre du numéro d\'identification';
$string['configcohortidnumberfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcohortnameattribute'] = 'Attribut de nom de cohorte';
$string['configcohortnameattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de cohorte';
$string['configcohortnamefilter'] = 'Filtre du nom de cohorte';
$string['configcohortnamefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcohortdescriptionattribute'] = 'Attribut de description';
$string['configcohortdescriptionattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de cohorte';
$string['configcohortmembershipattribute'] = 'Attribut d\'appartenance à la cohorte';
$string['configcohortmembershipattribute_desc'] = 'Attribut LDAP pour désigner les membres de la cohorte';
$string['configcohortmembershipfilter'] = 'Filtre d\'appartenance à la cohorte';
$string['configcohortmembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcohortuseridentifier'] = 'Identifiant interne d\'appartenance à la cohorte';
$string['configcohortuseridentifier_desc'] = '';
$string['id'] = 'Id primaire';

$string['deletingcohorts'] = 'Suppression des cohortes';
$string['creatingcohorts'] = 'Création des nouvelles cohortes';
$string['updatingcohorts'] = 'Mise à jour des cohortes';
$string['cohortdeleted'] = 'Cohort {$a} supprimée';
$string['cohortcreated'] = 'Cohort {$a} créée';
$string['cohortupdated'] = 'Cohort {$a->name} mise à jour. Numéro d\'identification : {$a->idnumber}';
$string['cohortmemberadded'] = 'Membre {$a->username} ajouté à la cohorte {$a->idnumber}';
$string['cohortmemberremoved'] = 'Membre {$a->username} supprimé de la cohorte {$a->idnumber}';
$string['disableautocohortscheck'] = 'Désactiver le verrou de cohortes automatiques';

// Rôles système.

$string['roleassignsfilters'] = 'Filtres pour l\association de rôles';

$string['configroleassigncontexts'] = 'Contextes pour les associations de rôle';
$string['configroleassigncontexts_desc'] = 'Les contextes LDAP où sont stockées les associations de rôle. Plusieurs contextes peuvent être donnés, séparés par des ;';
$string['configroleassignobjectclass'] = 'Classes d\'objets des associations de rôle';
$string['configroleassignobjectclass_desc'] = 'Les classes d\'objet ldap légitimes à détenir des associations de rôle';
$string['configroleassignidattribute'] = 'Attribut des associations de rôle';
$string['configroleassignidattribute_desc'] = 'L\'attribut LDAP pour trouver une association de rôle. Habituellement le DN.';
$string['configroleassignselectorfilter'] = 'Filtre de sélection des groupes d\'association de rôle';
$string['configroleassignselectorfilter_desc'] = 'Un filtre LDAP permettant de récupérer toutes les associations de rôle. Le remplacement d\'un emplacement %ID% par un identifiant d\'institution est traité. Si plusieurs Rne sont associés à l\'établissement, le filtre sera activé pour chaque établissement.';
$string['configroleassignroleattribute'] = 'Attribut du rôle';
$string['configroleassignroleattribute_desc'] = 'L\'attribut LDAP pour extraire le rôle à assigner';
$string['configroleassignrolefilter'] = 'Filtre du rôle';
$string['configroleassignrolefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassignrolemapping'] = 'Table de transcodage du rôle';
$string['configroleassignrolemapping_desc'] = 'Une table de transcodage des identifiants de rôle, donnée sous forme d\'une liste de paires, une paire par ligne, sous le format "entrée => sortie". Les espaces avant et arrière de chaque prédicat sont supprimés. L\'ensemble de sortie sont les noms courts des rôles Moodle.';
$string['configroleassigncontextlevelattribute'] = 'Attribut du niveau de contexte';
$string['configroleassigncontextlevelattribute_desc'] = 'L\'attribut LDAP pour extraire le niveau de contexte.';
$string['configroleassigncontextlevelfilter'] = 'Filtre du niveau de contexte';
$string['configroleassigncontextlevelfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassigncontextlevelmapping'] = 'Table de transcodage des niveaux de contexte';
$string['configroleassigncontextlevelmapping_desc'] = 'Une table de transcodage des identifiants de rôle, donnée sous forme d\'une liste de paires, une paire par ligne, sous le format "entrée => sortie". Les espaces avant et arrière de chaque prédicat sont supprimés. L\'ensemble de sortie est : \'system\', \'coursecat\', \'course\, \'module\', \block\', ou \'user\'.';
$string['configroleassigncontextattribute'] = 'Attribut LDAP du context cible';
$string['configroleassigncontextattribute_desc'] = 'L\'attribut LDAP contenant l\'information sur le contexte cible pour l\'attribution du rôle.';
$string['configroleassigncontextfilter'] = 'Filter du contexte';
$string['configroleassigncontextfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassigncoursecatkey'] = 'Clef primaire des catégories de cours';
$string['configroleassigncoursecatkey_desc'] = 'Le champ Moodle pour identifier la catégorie de cours.';
$string['configroleassigncoursekey'] = 'Clef primaire des cours';
$string['configroleassigncoursekey_desc'] = 'Le champ Moodle pour identifier le cours.';
$string['configroleassignmodulekey'] = 'Clef primaire des modules';
$string['configroleassignmodulekey_desc'] = 'Le champ Moodle pour identifier un module de cours.';
$string['configroleassignblockkey'] = 'Clef primaire des blocs';
$string['configroleassignblockkey_desc'] = 'Le champ Moodle pour identifier un bloc.';
$string['configroleassigntargetuserkey'] = 'Clef primaire de l\'utilisateur sujet';
$string['configroleassigntargetuserkey_desc'] = 'Le champ Moodle pour identifier l\'utilisateur sujet de l\'attribution de rôle.';
$string['configroleassignuserkey'] = 'Clef primaire de l\'utilisateur objet';
$string['configroleassignuserkey_desc'] = 'Le champ Moodle pour identifier l\'utilisateur sujet de l\'attribution de crôle.';
$string['configroleassignmembershipattribute'] = 'Attribut des éléments d\'association';
$string['configroleassignmembershipattribute_desc'] = 'L\'attribut LDAP permettant de lister les bénéficiares de l\'attribution';
$string['configroleassignmembershipfilter'] = 'Filtre des éléments d\'association';
$string['configroleassignmembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';

// Groupes

$string['groupsfilters'] = 'Filtres LDAP pour les groupes';
$string['configgroupcontexts'] = 'Contextes des groupes';
$string['configgroupcontexts_desc'] = 'Contextes pour trouver les groupes de groupes. Plusieurs contextes possibles séparés par des ;';
$string['configgroupautonameprefix'] = 'Préfixe des noms de groupes auto';
$string['configgroupautonameprefix_desc'] = 'Préfixe appliqué au nom des groupes créés automatiquement';
$string['configgroupobjectclass'] = 'Classes d\'objets des groupes';
$string['configgroupobjectclass_desc'] = 'Filtre en syntaxe LDAP pour restreindre les classes d\'objets ldap examinées.';
$string['configgroupselectorfilter'] = 'Filtre de sélection des groupes';
$string['configgroupselectorfilter_desc'] = 'Filtre en syntaxe LDAP pour extraire les identifiants de groupes. Admet un remplacement %ID% pour le Rne. Si plusieurs Rne sont associés à l\'établissement, le filtre sera activé pour chaque établissement';
$string['configgroupidattribute'] = 'Attribut identifiant LDAP de groupe';
$string['configgroupidattribute_desc'] = 'Attribut pour rechercher un enregistrement unique de groupe';
$string['configgroupidpattern'] = 'Motif d\'identifant de groupe';
$string['configgroupidpattern_desc'] = 'Un motif à remplacements pour construire la valeur de l\'identifiant. Accepte les emplacements %ID% (Rne) et %CID% (identifiant interne de groupe).';
$string['configgroupidnumberattribute'] = 'Attribut du numéro d\'identification';
$string['configgroupidnumberattribute_desc'] = 'Attribut LDAP pour extraire le numéro d\'identification de groupe';
$string['configgroupidnumberfilter'] = 'Filtre du numéro d\'identification';
$string['configgroupidnumberfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupgroupingattribute'] = 'Attribut du groupement';
$string['configgroupgroupingattribute_desc'] = 'Attribut LDAP pour extraire le nom du groupement. Si ce champ est vide, le groupement n\'est pas évalue';
$string['configgroupgroupingfilter'] = 'Filtre du groupement';
$string['configgroupgroupingfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupnameattribute'] = 'Attribut de nom de groupe';
$string['configgroupnameattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de groupe';
$string['configgroupnamefilter'] = 'Filtre du nom de groupe';
$string['configgroupnamefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupdescriptionattribute'] = 'Attribut de description';
$string['configgroupdescriptionattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de groupe';
$string['configgroupmembershipattribute'] = 'Attribut d\'appartenance à la groupe';
$string['configgroupmembershipattribute_desc'] = 'Attribut LDAP pour désigner les membres du groupe';
$string['configgroupmembershipfilter'] = 'Filtre d\'appartenance au groupe';
$string['configgroupmembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupuseridentifier'] = 'Identifiant interne d\'appartenance au groupe';
$string['configgroupuseridentifier_desc'] = 'L\'identifiant utilisateur moodle de référence pour l\'attribution des groupes';

$string['deletinggroups'] = 'Suppression des groupes';
$string['creatinggroups'] = 'Création des nouvelles groupes';
$string['updatinggroups'] = 'Mise à jour des groupes';
$string['groupdeleted'] = 'Groupe {$a->name} supprimée dans le cours {$a->course}';
$string['groupcreated'] = 'Groupe {$a->name} créée dans le cours {$a->course}';
$string['groupupdated'] = 'Groupe {$a->name} mise à jour dans le cours {$a->course}';
$string['groupmemberadded'] = 'Membre {$a->username} ajouté au groupe {$a->name} dans le cours {$a->course}';
$string['groupmemberremoved'] = 'Membre {$a->username} supprimé du groupe {$a->name} dans le cours {$a->course}';
$string['disableautogroupscheck'] = 'Désactiver le verrou de groupes automatiques';
