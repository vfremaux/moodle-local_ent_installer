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

$string['ent_installer:sync'] = 'Synchroniser manuellement les comptes utilisateur.';
$string['ent_installer:systemassign'] = 'Assigner des rôles dans le contexte système.';

// Privacy.
$string['privacy:metadata'] = 'Le composant local Installation ENT ne détient directement aucune donnée relative aux utilisateurs.';

$string['automation'] = 'Synchronisations de données';
$string['automatedcohortmembers'] = 'Membres de la cohorte automatique';
$string['backtocohorts'] = 'Retour aux cohortes';
$string['backtosettings'] = 'Retour aux réglages';
$string['backtosite'] = 'Retour au site';
$string['bycity'] = 'Par ville';
$string['byid'] = 'Par RNE';
$string['byname'] = 'Par nom';
$string['cleaningautomatedcohortscontent'] = 'Nettoyage des {$a} alimentations de cohortes automatiques.';
$string['clear'] = 'Supprimer';
$string['cohorts'] = 'Cohortes';
$string['courses'] = 'Cours';
$string['coursecats'] = 'Catégories de cours';
$string['configadminssitecohortname'] = 'Nom de la cohorte de site des administrateurs';
$string['configadminssitecohortname_desc'] = '';
$string['configadminstaffsitecohortname'] = 'Nom de la cohorte de site des personnels administratifs non enseignant';
$string['configadminstaffsitecohortname_desc'] = '';
$string['configauxgroupnamefilter'] = 'filtre d\'extraction des noms de groupes auciliaires';
$string['configauxgroupnamefilter_desc'] = 'Une expression régulière pour capturer le nom du groupe. Le résultat sera préfixé par le millésime.';
$string['configinstitutionaliasing'] = 'Aliasing d\'institution';
$string['configinstitutionaliasing_desc'] = 'Usage de l\'alias lors des imports d\'utilisateur.';
$string['configbuildteachercategory'] = 'Construire la catégorie enseignant';
$string['configbuildteachercategory_desc'] = 'Si actif, tout nouvel enseignant importé se verra attribuer une catégorie de cours à son nom dont il sera gestionnaire dans l\'espace pédagogique des enseignants.';
$string['configcohortindex'] = 'Préfixe de cohorte';
$string['configcohortindex_desc'] = 'Ce préfixe est ajouté aux noms de cohortes générées. Ce prefixe devrait être changé lors d\'un changement de session pédagogique (année scolaire) pour générer et maintenir un nouveau jeu de cohortes pour la session.';
$string['configcohortsortprefixlength'] = 'Longueur du préfixe de tri des cohortes';
$string['configcohortsortprefixlength_desc'] = '';
$string['configcompositeseparator'] = 'Séparateur de donnée composite';
$string['configcompositeseparator_desc'] = 'Définit globalement le séparateur de données composites comportant plusieurs sous-éléments distincts.';
$string['configlistseparator'] = 'Séparateur de liste';
$string['configlistseparator_desc'] = 'Définit le séparateur d\'éléments de listes.';
$string['configcreatecohortsfromuserrecords'] = 'Creer les cohortes à partir des entrées utilisateur';
$string['configcreatecohortsfromuserrecords_desc'] = 'Si actif, crée les cohortes à partir des fiches d\'utilisateur à partir du champ ENTClasses. Désactivez cette options lorsque l\'annuaire contient des définitions complètes des groupes et que la synchro des cohortes est active.';
$string['configcreateadminstaffsitecohort'] = 'Créer la cohorte de site des personnels non enseignants';
$string['configcreateadminstaffsitecohort_desc'] = '';
$string['configcreatestaffsitecohort'] = 'Créer la cohorte de site des enseignants';
$string['configcreatestaffsitecohort_desc'] = '';
$string['configcreatestudentssitecohort'] = 'Créer la cohorte de site des élèves';
$string['configcreatestudentssitecohort_desc'] = '';
$string['configcronenable'] = 'Intégration par le cron';
$string['configcronenable_desc'] = 'Activez cette option si vous voulez laisser effectuer l\'intégration des utilisateurs par le cron Moodle. Désactivez cette option si vous prévoyez de planifier ce traitement par vous-même (mode CLI).';
$string['configcrontime'] = 'Heure de traitement';
$string['configenroldeans'] = 'Nommer les directeurs';
$string['configenroldeans_desc'] = 'Si actif, donne les droits de gestionnaire aux directeus d\'établissement identifiés par \'DIR\' (champ ENTPersonFonctions)';
$string['configentuserinfoattributes'] = 'Liste d\'attributs LDAP à récupérer pour l\'alimentation complète du profil';
$string['configentuserinfoattributes_desc'] = 'Une liste de noms d\'attributs séparée par des virgules (sans espaces)';
$string['configfakemaildomain'] = 'Domaine des mails autogénérés';
$string['configfakemaildomain_desc'] = ' Domaine utilisé pour générer des adresses mail factices lorsqu\'elles sont manquantes dans les profils importés';
$string['configgetid'] = 'ID de structure';
$string['configgetinstitutionidservice'] = 'Chercher un ID d\'établissement';
$string['configinitialcategories'] = 'Catégories initiales';
$string['configinitialcategories_desc'] = 'Une description JSON des catégories initiales dont l\installeur ENT doit assurer l\'existance et la liaison à certains plugins.';
$string['configinstallcategories'] = 'Installer les catégories initiales';
$string['configinstallcats'] = 'Catégories';
$string['configinstitutionid'] = 'ID Etablissement';
$string['configinstitutionid_desc'] = 'L\'identifiant de jointure d\'établissement Education Nationale';
$string['configlastsyncdate'] = 'Dernière synchro';
$string['configlastsyncdate_desc'] = 'Dernière date de synchro. Si vous changez cette date, la prochaine synchro considèrera tous les utilisateur créés ou modifiés à partir de cette date.';
$string['configmaildisplay'] = 'Visibilité des adresses courriel initiales';
$string['configmaildisplay_desc'] = 'La visibilité initiale des adresses courriel pour les utilisateurs nouvellement créés.';
$string['configmailcheckusers'] = 'Utilisateurs à informer';
$string['configmailcheckusers_desc'] = 'Liste des id ou noms d\'utilisateur à informer.';
$string['configrealauth'] = 'Méthode d\'authentification effective';
$string['configrealauth_desc'] = 'Ce réglage définit la méthode d\'authentification à attribuer aux comptes synchronisés de l\'ENT, indépendamment du plugin utilisé pour contacter l\'annuaire.';
$string['configrecorddatefieldname'] = 'Champ de datation';
$string['configrecorddatefieldname_desc'] = 'Le nom du champ LDAP fournissant l\'information de dernière modification';
$string['configstaffsitecohortname'] = 'Nom de la cohorte de site des enseignants';
$string['configstaffsitecohortname_desc'] = '';
$string['configstudentssitecohortname'] = 'Nom de la cohorte de site des élèves';
$string['configstudentssitecohortname_desc'] = '';
$string['configsynccohortsenable'] = 'Activer les cohortes';
$string['configsyncenable'] = 'Actif';
$string['configsyncenable_desc'] = 'Active la synchronisation régulière des données ENT (CLI). Si désactivé, le script de synchronisation n\'aura aucun effet même s\'il est lancé par cron.';
$string['configsyncgroupsenable'] = 'Activer les groupes de cours';
$string['configsynccohorttocourseenable'] = 'Activer la synchro des liaisons de cohortes aux cours';
$string['configsyncsystemrolesenable'] = 'Activer l\'attribution de roles système';
$string['configsyncusersenable'] = 'Activer les comptes utilisateur';
$string['configteachermaskfirstname'] = 'Masquer le prénom des enseignants';
$string['configteachermaskfirstname_desc'] = 'Si actif, le prénom des enseignants sera réduit aux initiales dans les catégories propres des enseignants';
$string['configteacherstubcategory'] = 'Container espaces enseignants';
$string['configteacherstubcategory_desc'] = 'La catégorie contenant les containers de cours propres aux enseignants';
$string['configtimestampshift'] = 'Décalage d\'horloge';
$string['configtimestampshift_desc'] = 'Un temps de décalage ajouté aux dates provenant de l\'annuaire';
$string['configupdateinstitutionstructure'] = 'Mettre à jour la structure établissement';
$string['configupdateinstitutionstructure_desc'] = 'Si actif, la structure de l\'établissement (catégories de classes) est mise à jour avant chaque synchronisation.';
$string['configprotectcategoriesfromrelocate'] = 'Catégories protégées';
$string['configprotectcategoriesfromrelocate_desc'] = 'Une liste à virgules des id de catégories de cours dont les cours NE DOIVENT PAS être déplacés lors d\'un rappatriement de cours des enseignants.';
$string['configtimestampformat'] = 'Format du champ de datation';
$string['configtimestampformat_desc'] = 'Format unix UTC ou Active Directory (YYYYMMDDHHIISS.TZ)';
<<<<<<< HEAD
=======
$string['configprocessauxgroups'] = 'Traiter les groupes auxiliaires';
$string['configprocessauxgroups_desc'] = 'Traite le champ ENSAuxEnsGroupes pour forger des cohortes supplémentaires de groupes de spécialité';
>>>>>>> MOODLE_39_STABLE
$string['coursefilters'] = 'Filtres de cours';
$string['coursegroups'] = 'Groupes des cours';
$string['cohort'] = 'Cohorte';
$string['datasync'] = 'Synchronisation de données ENT';
$string['datasyncsettings'] = 'Réglages de la synchronisation de données ENT';
$string['dbinsertuser'] = 'ALIMENTATION : Création utilisateur {$a->username} - [{$a->idnumber}] Type utilisateur : {$a->usertype} / {$a->function}';
$string['dbinsertusersimul'] = 'SIMULATION : Création utilisateur {$a->username} - [{$a->idnumber}] Type utilisateur : {$a->usertype} / {$a->function}';
$string['dbskipnewuser'] = 'IGNORE : Nouvel utilisateur ignoré {$a->username} - [{$a->idnumber}] Type utilisateur : {$a->usertype} / {$a->function}';
$string['dbskipnewusersimul'] = 'SIMULATION IGNORE : Nouvel utilisateur ignoré {$a->username} - [{$a->idnumber}] Type utilisateur : {$a->usertype} / {$a->function}';
$string['dbupdateuser'] = 'ALIMENTATION : Mise à jour (heuristique d\'existance : {$a->status}) utilisateur {$a->username} - [{$a->idnumber}] Type utilisateur : {$a->usertype} / {$a->function}';
$string['dbupdateusersimul'] = 'SIMULATION : Mise à jour (heuristique d\'existance : {$a->status}) utilisateur {$a->username} - [{$a->idnumber}] Type utilisateur : {$a->usertype} / {$a->function}';
$string['dbusertodelete'] = 'Utilisateur {$a->username} marqué pour SUPPRESSION - Supression de [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['dbusertosuspend'] = 'Utilisateur {$a->username} marqué pour SUPPRESSION - Suspension de [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['dbusertodeletedonothing'] = 'Utilisateur {$a->username} marqué pour SUPPRESSION - MAIS Conservation de [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['dbusertodeletesimul'] = 'SIMULATION : Utilisateur {$a->username} marqué pour SUPPRESSION - Suppression de [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['dbusertosuspendsimul'] = 'SIMULATION : Utilisateur {$a->username} marqué pour SUPPRESSION - Suspension de [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['dbusertodeletedonothingsimul'] = 'SIMULATION : Utilisateur {$a->username} marqué pour SUPPRESSION - MAIS conservation de [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['usertoskipasdeleted'] = 'Nouvel utilisateur {$a->username} marqué pour SUPPRESSION - [{$a->idnumber}] user type : {$a->usertype} / {$a->function} ignoré';
$string['usertoskipasdeletedsimul'] = 'SIMULATION : Nouvel utilisateur {$a->username} marqué pour SUPPRESSION - [{$a->idnumber}] user type : {$a->usertype} / {$a->function}';
$string['defaultarchivecatname'] = 'Corbeille';
$string['didntgetcohortsfromldap'] = 'Aucune cohorte remontée du LDAP. Arrêt du traitement.';
$string['didntgetusersfromldap'] = 'Aucun utilisateur remontée du LDAP. Arrêt du traitement.';
$string['doit'] = 'Créer les catégories manquantes';
$string['emptygroups'] = 'Groupes vides';
$string['emulatecommunity'] = 'Emuler la version communautaire';
$string['emulatecommunity_desc'] = 'Bascule le code sur la version communautaire. Le résultat est plus compatible avec d\'autres installations, mais certaines fonctionnalités avancées ne seront plus disponibles.';
$string['enrolled'] = 'Inscrit par la méthode {$a}';
$string['enrolmethod'] = 'Méthode d\'inscription';
$string['entities'] = 'Entités';
$string['entupdate'] = 'Réalimenter les données d\'établissement';
$string['filter'] = 'Filtrer';
$string['finaloperations'] = 'Opérations finales';
$string['force'] = 'Forcer la mise à jour de toutes les entrées (y compris celles non modifiées dans la période de référence)';
$string['forcedebugwarning'] = 'You cannot enable forced mode when being in developer debug mode as this will get partial info from ldap';
$string['getinstitutionidservice'] = 'Recherche d\'identifiants d\'établissements';
$string['generals'] = 'Réglages généraux';
$string['gotcountrecordsfromldap'] = '{$a} enregistrements de cohorts trouvés dans l\'annuaire.';
$string['id'] = 'Identifiant RNE';
$string['moodleid'] = 'ID Moodle interne';
$string['inserterrors'] = 'Erreurs d\'insertion';
$string['inserts'] = 'Insertions (enregistrements ajoutés)';
$string['installcats'] = 'Installation des catégories initiales';
$string['lastrun'] = 'Dernière exécution {$a}';
$string['lasttime'] == 'Dernier passage';
$string['licensestatus'] = 'Etat de la license d\'exploitation';
$string['licenseprovider'] = 'Fournisseur version Pro';
$string['licenseprovider_desc'] = 'Entrez la clef de votre distributeur.';
$string['licensekey'] = 'Clef de license pro';
$string['licensekey_desc'] = 'Entrez ici la clef de produit que vous avez reçu de votre distributeur.';
$string['maxduration'] = 'Durée max';
$string['meantime'] = 'Moyenne';
$string['mergesiteadmins'] = 'Mise à jour des administrateurs de site à {$a}';
$string['minduration'] = 'Durée min';
$string['miscsettings'] = 'Réglages divers';
$string['module'] = 'Module de cours';
$string['noenrol'] = 'Aucune';
$string['noresults'] = 'Aucun résultat';
$string['normalmeantime'] = 'Moyenne usuelle (sans dépassements)';
$string['nothingtodo'] = 'Aucune donnée à traiter';
$string['nousers'] = 'Aucun utilisateur dans la cohorte';
$string['oldcohortdeleted'] = 'Cohorte obsolète {$a} supprimée';
$string['options'] = 'Options de traitement';
$string['othercommands'] = 'Autres commandes';
$string['overtime'] = 'Dépassements';
$string['overtimes'] = 'Dépassement de temps critique (> {$a} secs)';
$string['plugindist'] = 'Distribution du plugin';
$string['pluginname'] = 'Installation spécifique Moodle ENT';
$string['purgeobsoletecohorts'] = 'Supprimer les cohortes obsoletes';
$string['refreshsitemetadata'] = 'Raffraichir les métadonnées de site';
$string['relocatingcourse'] = 'Cours {$a} relocalisé.';
$string['relocatingcourseignored'] = 'Cours {$a} non déplacé (catégorie protégée).';
$string['reset'] = 'Mettre à zéro les statistiques';
$string['resetallvnodes'] = 'Mettre à zéro toutes les statistiques';
$string['revivingdeletedorsuspended'] = 'Réactiver des utilisateurs suspendus ou supprimés';
$string['roleassigns'] = 'Associations de rôles';
$string['runsync'] = 'Lancer la synchronisation';
$string['search'] = 'Recherche';
$string['seedetail'] = '(Voir la liste)';
$string['simulate'] = 'Mode simulation';
$string['sitecategories'] = 'Catégories de site';
$string['structuresearch'] = 'Paramètres pour la recherche de structures';
$string['syncbench'] = 'Mesure des temps de synchronisation';
$string['syncbenchreport_desc'] = 'Un <a href="{$a}">rapport sur les temps de synchronisation</a>';
$string['synccohortsdisabled'] = 'La synchro des cohortes est désactivée sur ce site';
$string['syncdisabled'] = 'La synchro est désactivée sur ce site';
$string['syncgroupsdisabled'] = 'La synchro des groupes de cours est désactivée sur ce site';
$string['synchronisemoodle'] = 'Synchroniser les données de moodle';
$string['syncroleassignsdisabled'] = 'La synchro des associations de rôle est désactivée sur ce site';
$string['syncsingleuser'] = 'Synchroniser un utilisateur unique';
$string['syncsinglecourse'] = 'Synchroniser un cours unique';
$string['syncsinglecohort'] = 'Synchroniser une cohorte unique';
$string['synctime'] = 'Temps de syncro';
$string['synctimes'] = 'Temps de synchro';
$string['synctimetitle'] = 'Mesure des temps de chargement/mise à jour utilisateurs';
$string['syncusers'] = 'Synchro';
$string['syncusers_desc'] = '<a href="{$a}">Lancer une synchronisation manuelle</a>';
$string['syncusersdisabled'] = 'La synchro des comptes utilisateur est désactivée sur ce site';
$string['teachercatreorder'] = 'Réordonner les catégories enseignants';
$string['relocateteachercourses'] = 'Rappatrier les cours enseignants dans leur catégorie';
$string['unenrolled'] = 'Désinscrit de la méthode {$a}';
$string['doall'] = 'Faire toutes les opérations.';
$string['updateerrors'] = 'Erreur de mises à jour';
$string['updateonly'] = 'Uniquement raffraichir les enregistrements existants. Ne pas en créer de nouveaux.';
$string['createonly'] = 'Uniquement créer les nouveaux.';
<<<<<<< HEAD
$string['deleteonly'] = 'Uniquement supprimer les enregsitrements obsolètes.';
$string['updates'] = 'Mises à jour (utilisateurs modifiés)';
=======
$string['deleteonly'] = 'Uniquement supprimer les enregistrements obsolètes.';
$string['updates'] = 'Mises à jour (enregistrements modifiés)';
>>>>>>> MOODLE_39_STABLE
$string['updatingusers'] = 'Mise à jour des utilisateurs (attributs seulement)';
$string['users'] = 'Comptes utilisateur';
$string['usersdeletion'] = 'Suppression des utilisateurs';
$string['verbose'] = 'Plus de sorties de trace';
$string['wsallhosts'] = 'Récupérer pour tous les hôtes (vmoodle)';
$string['wsdateformat'] = 'Format de date';
$string['wsentsyncdate'] = 'Dernière synchronisation des utilisateurs';
$string['wslastcron'] = 'Dernier cron connu du site';
$string['wssetting'] = 'Date à observer';
$string['unalias'] = 'Utiliser les identifiants individuels d\'établissement';
$string['usealias'] = 'Utiliser uniquement l\'alias';
$string['addalias'] = 'Ajouter l\'alias aux indentifiants d\'établissement';
$string['nothingdone'] = 'Aucun traitement';
$string['settingsusers'] = 'Synchronisation des utilisateurs';
$string['settingscourses'] = 'Synchronisation des cours';
$string['settingscohorts'] = 'Synchronisation des cohortes';
$string['settingsroleassigns'] = 'Synchronisation des affectations de rôle';
$string['settingscoursegroups'] = 'Synchronisation des groupes de cours';
$string['settingsstructures'] = 'Information sur les établissements';
$string['ldapfilters'] = 'Filtres ldap';
<<<<<<< HEAD
=======
$string['usefilternotice'] = 'Plus de 200 utilisateurs sont inscrits. Utilisez le filtre pour réduire...';
>>>>>>> MOODLE_39_STABLE

$string['personfilters'] = 'Filtres LDAP pour les comptes utilisateur';
$string['configgenericusertypefilter'] = 'Discriminant des utilisateurs génériques';
$string['configgenericusertypefilter_desc'] = '';
$string['configgenericinstitutionfilter'] = 'Sélecteur d\'établissement des génériques';
$string['configgenericinstitutionfilter_desc'] = '';
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
$string['configuserpicturefield'] = 'Champ d\'avatar';
<<<<<<< HEAD
$string['configuserpicturefield_desc'] = 'Champ Ldap contenant l\'informaiton permettant de synthétiser une url vers l\'image de profil.';
=======
$string['configuserpicturefield_desc'] = 'Champ Ldap contenant l\'information permettant de synthétiser une url vers l\'image de profil.';
>>>>>>> MOODLE_39_STABLE
$string['configuserpicturefilter'] = 'Extracteur de l\'avatar';
$string['configuserpicturefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configuserpictureurlpattern'] = 'Motif de constrution de l\'url d\'avatar';
$string['configuserpictureurlpattern_desc'] = 'Un motif de construction d\'une URL acceptant la valeur %PICTURE% extraite du champ d\'avatar';

// Cohortes.

$string['cohortsfilters'] = 'Filtres LDAP pour les cohortes';
$string['configcohortcontexts'] = 'Contextes des cohortes';
$string['configcohortcontexts_desc'] = 'Contextes pour trouver les groupes de cohortes. Plusieurs contextes possibles séparés par des ;';
$string['configcohortcoursebindingattribute'] = 'Attribut LDAP pour le raccordement de cours';
$string['configcohortcoursebindingattribute_desc'] = 'L\'attribut LDAP qui porte les identificants de cours à raccorder à la cohorte.';
$string['configcohortcoursebindingidentifier'] = 'Identifiant de cours';
$string['configcohortcoursebindingidentifier_desc'] = 'Le champ moodle permettant d\'identifier les cours..';
$string['configcohortobjectclass'] = 'Classes d\'objets des cohortes';
$string['configcohortobjectclass_desc'] = 'Filtre en syntaxe LDAP pour restreindre les classes d\'objets ldap examinées.';
$string['configcohortselectorfilter'] = 'Filtre de sélection des cohortes';
$string['configcohortselectorfilter_desc'] = 'Filtre en syntaxe LDAP pour extraire les identifiants de cohortes. Admet un remplacement %ID% pour le Rne. Si plusieurs Rne sont associés à l\'établissement, le filtre sera activé pour chaque établissement';
$string['configcohortidattribute'] = 'Attribut identifiant LDAP de cohorte';
$string['configcohortidattribute_desc'] = 'Attribut pour rechercher un enregistrement unique de cohorte (identifiant primaire du LDAP)';
$string['configcohortidpattern'] = 'Motif d\'identifiant de cohorte';
$string['configcohortidpattern_desc'] = 'Un motif à remplacements pour construire la valeur de l\'identifiant. Accepte les emplacements %ID% (Rne) et %CID% (identifiant interne de cohorte).';
$string['configcohortidnumberattribute'] = 'Attribut du numéro d\'identification';
$string['configcohortidnumberattribute_desc'] = 'Attribut LDAP pour extraire le numéro d\'identification de cohorte';
$string['configcohortidnumberfilter'] = 'Extracteur du numéro d\'identification';
$string['configcohortidnumberfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcohortnameattribute'] = 'Attribut de nom de cohorte';
$string['configcohortnameattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de cohorte';
$string['configcohortnamefilter'] = 'Extracteur du nom de cohorte';
$string['configcohortnamefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcohortdescriptionattribute'] = 'Attribut de description';
$string['configcohortdescriptionattribute_desc'] = 'Attribut LDAP pour extraire la description de la cohorte';
$string['configcohortmembershipattribute'] = 'Attribut d\'appartenance à la cohorte';
$string['configcohortmembershipattribute_desc'] = 'Attribut LDAP pour désigner les membres de la cohorte';
$string['configcohortmembershipfilter'] = 'Extracteur d\'appartenance à la cohorte';
$string['configcohortuseridentifier'] = 'Identifiant interne d\'appartenance à la cohorte';
$string['configcohortoldprefixes'] = 'Anciens prefixes';
$string['configcohorthardcohortunenrol'] = 'Mode de suppression des cohortes';
$string['configcohorthardcohortunenrol_desc'] = 'Ce qui se passe lorsqu\'une relation au cours disparait de l\'annuaire. Vous pouvez choisir entre la désactivation (sans suppression des données utilisateur liées à l\'inscription), ou la suppression avec désinscription.';
$string['id'] = 'Id primaire';

<<<<<<< HEAD
=======
$string['configsynccohorttocourseenable_desc'] = 'Active la synchronisation des liaisons entre les cours et les cohortes, gérant les méthodes d\'inscriptions par synchronisation de cohortes. Attention, dans ce cas les affectations manuelles de méthodes de synchonisation de cohortes ne peuvent plus être maintenues si elles ne sont
pas répercutées sur la référence externe de données.';

>>>>>>> MOODLE_39_STABLE
$string['configcohortuseridentifier_desc'] = 'Champ interne d\'identifiant utilisateur utilisé pour trouver l\'ID utilisateur';

$string['configcohortmembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier
sous-motif disponible';

$string['configcohortuseridentifierlocked_desc'] = 'Champ interne d\'identifiant utilisateur utilisé pour trouver l\'ID utilisateur.
La valeur est forcée par la configuration "L\'attribut appartenance utilise dn" actuelle du plugin d\'authentification LDAP.';

$string['configcohortoldprefixes_desc'] = 'Prefixes (liste à virgule) des cohortes obsolètes à supprimer';

$string['cohortnobindings'] = 'Aucune liaison de cohorte';
$string['cohortbindings'] = 'Liaison des cohortes aux cours';
$string['deletingcohorts'] = 'Suppression des cohortes';
$string['creatingcohorts'] = 'Création des nouvelles cohortes';
$string['updatingcohorts'] = 'Mise à jour des cohortes';
$string['cohortdeleted'] = 'Cohorte {$a} supprimée';
$string['cohortcreated'] = 'Cohorte {$a->name} [{$a->idnumber}] créée';
$string['cohortupdated'] = 'Cohorte {$a->name} mise à jour. Numéro d\'identification : {$a->idnumber}.';
$string['cohortmemberadded'] = 'Membre {$a->username} [{$a->uidnumber}] ajouté à la cohorte {$a->idnumber}.';
$string['cohortmemberexists'] = 'Membre {$a->username} [{$a->uidnumber}] existe dans la cohorte {$a->idnumber}.';
$string['cohortmemberremoved'] = 'Membre {$a->username} [{$a->uidnumber}] supprimé de la cohorte {$a->idnumber}.';
$string['cohortbindingadded'] = 'Inscription par cohorte {$a->idnumber} ajoutée au cours {$a->shortname} [{$a->cidnumber}] avec le role {$a->role}';
$string['cohortbindingexists'] = 'Inscription par cohorte {$a->idnumber} conservée dans le cours {$a->shortname} [{$a->cidnumber}] avec le role {$a->role}.';
$string['cohortbindingremoved'] = 'Inscription par cohorte {$a->idnumber} supprimée du cours {$a->shortname} [{$a->cidnumber}] avec le role {$a->role}';
$string['cohortbindingenabled'] = 'Inscription par cohorte {$a->idnumber} réactivée dans le cours {$a->shortname} [{$a->cidnumber}] avec le role {$a->role}.';
$string['cohortbindingdisabled'] = 'Inscription par cohorte {$a->idnumber} désactivée dans le cours {$a->shortname} [{$a->cidnumber}] avec le role {$a->role}.';
$string['disableautocohortscheck'] = 'Désactiver le verrou de cohortes automatiques';
$string['removingoldcohort'] = 'Suppression de la cohorte obsolète {$a->id} {$a->name}';

// Role assigns.

$string['roleassignsfilters'] = 'Filtres pour l\'association de rôles';

$string['configsyncroleassignsenable'] = 'Activer la synchro des associations de rôle';
$string['configsyncroleassignsenable_desc'] = '';
$string['configroleassigncontexts'] = 'Contextes pour les associations de rôle';
$string['configroleassigncontexts_desc'] = 'Les contextes LDAP où sont stockées les associations de rôle. Plusieurs contextes peuvent être donnés, séparés par des ;';
$string['configroleassignusealias'] = 'Utiliser l\'alias de l\'institution';
$string['configroleassignusealias_desc'] = 'Si actif, la synchronisaton ne tournera qu\'une fois sur le nom d\'alias de l\'institution. Ceci suppose que toutes les données intégrées soient indexées sur le nom d\'alias';
$string['configroleassignobjectclass'] = 'Classes d\'objets des associations de rôle';
$string['configroleassignobjectclass_desc'] = 'Les classes d\'objet ldap légitimes à détenir des associations de rôle';
$string['configroleassignidattribute'] = 'Attribut des associations de rôle';
$string['configroleassignidattribute_desc'] = 'L\'attribut LDAP pour trouver une association de rôle. Habituellement le DN.';
$string['configroleassignselectorfilter'] = 'Filtre de sélection des groupes d\'association de rôle';
$string['configroleassignselectorfilter_desc'] = 'Un filtre LDAP permettant de récupérer toutes les associations de rôle. Le remplacement d\'un emplacement %ID% par un identifiant d\'institution est traité. Si plusieurs Rne sont associés à l\'établissement, le filtre sera activé pour chaque établissement.';
$string['configroleassignroleattribute'] = 'Attribut du rôle';
$string['configroleassignroleattribute_desc'] = 'L\'attribut LDAP pour extraire le rôle à assigner';
$string['configroleassignrolefilter'] = 'Extracteur du rôle';
$string['configroleassignrolefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassignrolemapping'] = 'Table de transcodage du rôle';
$string['configroleassignrolemapping_desc'] = 'Une table de transcodage des identifiants de rôle, donnée sous forme d\'une liste de paires, une paire par ligne, sous le format "entrée => sortie". Les espaces avant et arrière de chaque prédicat sont supprimés. L\'ensemble de sortie sont les noms courts des rôles Moodle.';
$string['configroleassigncontextlevelattribute'] = 'Attribut du niveau de contexte';
$string['configroleassigncontextlevelattribute_desc'] = 'L\'attribut LDAP pour extraire le niveau de contexte.';
$string['configroleassigncontextlevelfilter'] = 'Extracteur du niveau de contexte';
$string['configroleassigncontextlevelfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassigncontextlevelmapping'] = 'Table de transcodage des niveaux de contexte';
$string['configroleassigncontextlevelmapping_desc'] = 'Une table de transcodage des identifiants de rôle, donnée sous forme d\'une liste de paires, une paire par ligne, sous le format "entrée => sortie". Les espaces avant et arrière de chaque prédicat sont supprimés. L\'ensemble de sortie est : \'system\', \'coursecat\', \'course\, \'module\', \block\', ou \'user\'.';
$string['configroleassigncontextattribute'] = 'Attribut LDAP du context cible';
$string['configroleassigncontextattribute_desc'] = 'L\'attribut LDAP contenant l\'information sur le contexte cible pour l\'attribution du rôle.';
$string['configroleassigncontextfilter'] = 'Extracteur du contexte';
$string['configroleassigncontextfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassigncoursecatkey'] = 'Clef primaire des catégories de cours';
$string['configroleassigncoursecatkey_desc'] = 'Le champ Moodle pour identifier la catégorie de cours.';
$string['configroleassigncoursekey'] = 'Clef primaire des cours';
$string['configroleassigncoursekey_desc'] = 'Le champ Moodle pour identifier le cours.';
$string['configroleassignmodulekey'] = 'Clef primaire des modules';
$string['configroleassignmodulekey_desc'] = 'Le champ Moodle pour identifier un module de cours.';
$string['configroleassignblockkey'] = 'Clef primaire des blocs';
$string['configroleassignblockkey_desc'] = 'Le champ Moodle pour identifier un bloc.';
$string['configroleassigntargetuserkey'] = 'Clef primaire de l\'utilisateur objet';
$string['configroleassigntargetuserkey_desc'] = 'Le champ Moodle pour identifier l\'utilisateur objet de l\'attribution de rôle (celui sur lequel agit le rôle).';
$string['configroleassignuserkey'] = 'Clef primaire de l\'utilisateur sujet';
$string['configroleassignuserkey_desc'] = 'Le champ Moodle pour identifier l\'utilisateur sujet de l\'attribution de rôle (celui qui reçoit l\'attribution).';
$string['configroleassignuserkeylocked_desc'] = 'Le champ Moodle pour identifier l\'utilisateur sujet de l\'attribution de rôle (celui qui reçoit l\'attribution).
La valeur est forcée par la configuration "L\'attribut appartenance utilise dn" actuelle du plugin d\'authentification LDAP.';
$string['configroleassignmembershipattribute'] = 'Attribut des éléments d\'association';
$string['configroleassignmembershipattribute_desc'] = 'L\'attribut LDAP permettant de lister les bénéficiares de l\'attribution';
$string['configroleassignmembershipfilter'] = 'Extracteur des éléments d\'association';
$string['configroleassignmembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configroleassignenrolmethod'] = 'Méthode d\'inscription';
$string['configroleassignenrolmethod_desc'] = 'Si défini, ajoutera une inscription en plus de l\'affectation de rôle. Ceci n\'agit que quand le contexte d\'affectation est un cours.';

$string['noenrol'] = 'Ajouter le rôle sans inscription';
$string['deletingroleassigns'] = 'Suppression d\'assignation de rôle';
$string['creatingroleassigns'] = 'Nouvelles assignations de rôle';
$string['unchangedroleassigns'] = 'Sans changement';
$string['roleassigned'] = 'Rôle {$a->roleinfo} ajouté pour  {$a->userinfo} dans {$a->contextinfo}';
$string['roleunassigned'] = 'Rôle {$a->roleinfo} supprimé pour {$a->userinfo} dans {$a->contextinfo}';
$string['norolechange'] = '{$a->roleinfo} maintenu pour {$a->userinfo} dans {$a->contextinfo}';

// Groups.

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
$string['configgroupidpattern_desc'] = 'Un motif à remplacements pour construire la valeur de l\'identifiant. Accepte les emplacements %ID% (Rne) et %GID% (numéro d\'identificaton du groupe), %CID% (id interne du cours), %CSHORTNAME% (nom court du cours, %CIDNUMBER% (Numéro d\'identification du cours).';
$string['configgroupidnumberattribute'] = 'Attribut du numéro d\'identification';
$string['configgroupidnumberattribute_desc'] = 'Attribut LDAP pour extraire le numéro d\'identification de groupe';
$string['configgroupidnumberfilter'] = 'Extracteur du numéro d\'identification';
$string['configgroupidnumberfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupnameattribute'] = 'Attribut du nom de groupe';
$string['configgroupnameattribute_desc'] = 'Attribut LDAP pour extraire le nom du groupe';
$string['configgroupnamefilter'] = 'Extracteur du nom de groupe';
$string['configgroupnamefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupcourseattribute'] = 'Attribut de l\'identifiant de cours';
$string['configgroupcourseattribute_desc'] = 'Attribut LDAP pour extraire l\'identifiant de cours';
$string['configgroupcoursefilter'] = 'Extracteur de l\'identifiant de cours';
$string['configgroupcoursefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupcourseidentifier'] = 'Champ moodle identifiant du cours';
$string['configgroupcourseidentifier_desc'] = 'Champ moodle de l\'enregistrement de cours contenant le groupe';
$string['configgroupgroupingattribute'] = 'Attribut du groupement';
$string['configgroupgroupingattribute_desc'] = 'Attribut LDAP pour extraire le nom du groupement. Si ce champ est vide, le groupement n\'est pas évalue';
$string['configgroupgroupingfilter'] = 'Extracteur du groupement';
$string['configgroupgroupingfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupnameattribute'] = 'Attribut de nom de groupe';
$string['configgroupnameattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de groupe';
$string['configgroupnamefilter'] = 'Extracteur du nom de groupe';
$string['configgroupnamefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupdescriptionattribute'] = 'Attribut de description';
$string['configgroupdescriptionattribute_desc'] = 'Attribut LDAP pour extraire le nom visible de groupe';
$string['configgroupmembershipattribute'] = 'Attribut d\'appartenance au groupe';
$string['configgroupmembershipattribute_desc'] = 'Attribut LDAP pour désigner les membres du groupe';
$string['configgroupmembershipfilter'] = 'Extracteur d\'appartenance au groupe';
$string['configgroupmembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configgroupuseridentifier'] = 'Identifiant interne d\'appartenance au groupe';
$string['configgroupuseridentifier_desc'] = 'L\'identifiant utilisateur moodle de référence pour l\'attribution des groupes';
$string['configgroupuseridentifierlocked_desc'] = 'L\'identifiant utilisateur moodle de référence pour l\'attribution des groupes.
La valeur est forcée par la configuration "L\'attribut appartenance utilise dn" actuelle du plugin d\'authentification LDAP.';
$string['configgroupsprotectformdeletion'] = 'Protection';
$string['configgroupsprotectformdeletion_desc'] = 'Si elle est activée, les groupes ne peuvent pas être supprimés par la synchronisation';

$string['deletinggroups'] = 'Suppression des groupes';
$string['creatinggroups'] = 'Création des nouvelles groupes';
$string['updatinggroups'] = 'Mise à jour des groupes';
$string['groupdeleted'] = 'Groupe {$a->name} supprimée dans le cours {$a->course}';
$string['groupcreated'] = 'Groupe [{$a->idnumber}] {$a->name} créée dans le cours {$a->courseid}';
$string['groupupdated'] = 'Groupe [{$a->idnumber}] {$a->name} mise à jour dans le cours {$a->courseid}';
$string['groupmemberadded'] = 'Membre {$a->username} ajouté au groupe {$a->idnumber} dans le cours {$a->course}';
$string['groupmemberremoved'] = 'Membre {$a->username} supprimé du groupe {$a->idnumber} dans le cours {$a->course}';
$string['groupmissinguser'] = 'Erreur : Utilisateur {$a->username} non connu';
$string['disableautogroupscheck'] = 'Désactiver le verrou de groupes automatiques';
$string['skipmembership'] = 'Ignorer les membres';
$string['skipmembership'] = 'Membres ignorés.';
$string['skipmembership_help'] = 'Si actif, seules les définitions du groupe sont mises à jour. Les inscriptions au groupes ne sont pas modifiées.';

// courses.

$string['configsynccoursecatsenable'] = 'Activer la synchro des catégories de cours';
$string['configcoursecatcontexts'] = 'Contextes LDAP des catégories de cours';
$string['configcoursecatcontexts_desc'] = 'Plusieurs contextes possibles séparés par des ; où trouver les groupes LDAP représentant des catégories de cours.';
$string['configcoursecatselectorfilter'] = 'Sélecteur des catégories de cours';
$string['configcoursecatselectorfilter_desc'] = 'Un filtre LDAP pour la sélection des groupes de catégories de cours';
$string['configcoursecatidattribute'] = 'Attribut LDAP de l\'id de catégorie';
$string['configcoursecatidattribute_desc'] = 'Attribut LDAP pour l\'identifiant primaire de catégorie.';
$string['configcoursecatidnumberattribute'] = 'Attribut LDAP identifiant de catégorie';
$string['configcoursecatidnumberattribute_desc'] = 'Attribut LDAP pour le numéro d\'identification externe de catégorie (idnumber).';
$string['configcoursecatidnumberfilter'] = 'Extracteur de l\'identifiant de catégorie';
$string['configcoursecatidnumberfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcoursecatidnumberpattern'] = 'Motif de l\'dentifiant de catégorie';
$string['configcoursecatidnumberpattern_desc'] = 'Motif à remplacement pour le numéro d\'identification de catégorie de cours. Accepte un emplacement %CCID%.';
$string['configcoursecatnameattribute'] = 'Attribut LDAP du nom de la catégorie';
$string['configcoursecatnameattribute_desc'] = 'Attribut LDAP pour le nom de la catégorie';
$string['configcoursecatnameisfullpath'] = 'Le nom de la catégorie est elle un chemin complet ?';
$string['configcoursecatnameisfullpath_desc'] = 'Cecher cette case si le nom de la catégorie est donné sous forme d\'un chemain complet à partir de la racine.';
$string['configcoursecatparentattribute'] = 'Attribut LDAP de la catégorie parente';
$string['configcoursecatparentattribute_desc'] = 'Attribut LDAP pour la référence de la catégorie parente. Utile lorsque les noms de catégorie ne sont pas donnés sous forme de chemin complet.';
$string['configcoursecatparentfilter'] = 'Extracteur de la catégorie parente';
$string['configcoursecatparentfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcoursecatparentpattern'] = 'Motif de l\'identifiant de parent';
$string['configcoursecatparentpattern_desc'] = 'Motif à remplacement pour le parent de la catégorie de cours. Accepte un emplacement %PCID%.';

$string['configsynccourseenable'] = 'Activer la synchro des cours';
$string['configcoursecontexts'] = 'Contextes LDAP des cours';
$string['configcourseselectorfilter'] = 'Filtre LDAP pour la sélection des cours';
$string['configcourseselectorfilter_desc'] = 'Filtre LDAP discriminant les entrées représentant des cours';
$string['configcourseidnumberattribute'] = 'Attribut LDAP du numéro d\'identification de cours';
$string['configcourseidnumberattribute_desc'] = 'Attribut LDAP du numéro d\'identification de cours';
$string['configcourseidattribute'] = 'Attribut LDAP de l\'identifiant de cours';
$string['configcourseidpattern'] = 'Motif pour l\'identifiant de cours';
$string['configcourseidpattern_desc'] = 'Motif à remplacement pour l\'identifiant de cours soumis au LDAP. Accepte des emplacements %CID% (identifiant local de cours) et %ID% (etablissement).';
$string['configcourseidnumberfilter'] = 'Filtre du numéro d\'identification du cours';
$string['configcourseidnumberfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcourseidnumberpattern'] = 'Motif pour le numéro d\'identification du cours';
$string['configcourseidnumberpattern_desc'] = 'Motif à remplacement pour le numéro d\'identification du cours. Accepte un emplacement %VALUE% générique.';
$string['configcoursefullnameattribute'] = 'Attribut LDAP du nom complet du cours';
$string['configcoursefullnameattribute_desc'] = 'Attribut LDAP du nom complet du cours';
$string['configcoursesummaryattribute'] = 'Attribut LDAP du résumé de cours';
$string['configcoursesummaryattribute_desc'] = 'Attribut LDAP du résumé de cours';
$string['configcoursevisibleattribute'] = 'Attribut LDAP de la vsibilité du cours';
$string['configcoursevisibleattribute_desc'] = 'Attribut LDAP de la vsibilité du cours. 0 ou faux valent pour non visible.';
$string['configcourseshortnameattribute'] = 'Attribut LDAP du nom court de cours';
$string['configcourseshortnamefilter'] = 'Filtre pour le nom court du cours';
$string['configcourseshortnamefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcourseshortnamepattern'] = 'Motif pour le nom court du cours';
$string['configcourseshortnamepattern_desc'] = 'Motif à remplacement pour le nom court du cours. Accepte un emplacement %VALUE% générique.';
$string['configcoursecategoryattribute'] = 'Attribut LDAP de la catégorie du cours';
$string['configcoursecategoryattribute_desc'] = 'L\'attribut LDAP donnant accès à la catégorie du cours sous forme d\'un chemin.';
$string['configcourseprimarykey'] = 'Clef de jointure primaire des cours';
$string['configcourseprimarykey_desc'] = 'Clef de jointure (champ moodle) de référence pour le rapprochement des cours avec le LDAP.';
$string['configcoursecategorypathseparator'] = 'Séparateur du chemin de la catégorie';
$string['configcoursecategorypathseparator_desc'] = 'Le séparateur utilisé pour séparer les parties de chemin de la catégorie de cours.';
$string['configcoursecategorysyntaxattribute'] = 'Syntaxe de la catégorie';
$string['configcoursecategorysyntaxattribute_desc'] = 'Choisit une syntaxe de décodage du chemin de catégorie. Une syntaxe composite attend
un chemin composé de paires idnumber<sep>nom_de_categorie. La syntaxe simple est un simple chemin de noms. (Voir le réglage du séparateur composite)';
$string['configcoursetemplateattribute'] = 'Attribut ldap du template';
$string['configcoursetemplateattribute_desc'] = 'L\'attribut LDAP qui fournit le nom ou une partie signifiante du nom du template. Si le préfixe "shortname:",
est présent, alors la valeur est considérée comme un nom court à part entière et ne sera pas patternisé. De même pour le préfixe "idnumber:".';
$string['configcoursetemplatefilter'] = 'Filtre pour le template';
$string['configcoursetemplatefilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible';
$string['configcoursetemplatepattern'] = 'Motif à replacement';
$string['configcoursetemplatepattern_desc'] = 'Motif à remplacement pour le template. Accepte un emplacement %VALUE% générique pour placer
la partie signifiante du nom du template.';
$string['configcoursetemplatedefault'] = 'Template de cours par défaut';
$string['configcoursetemplatedefault_desc'] = 'Une valeur par défaut de l\'identifiant ou position du template si la valeur vaut "default".';
$string['deletingcourses'] = 'Suppression des cours';
$string['updatingcourses'] = 'Mise à jour des cours';
$string['creatingcourses'] = 'Création des cours';
$string['configcourseenrolmethod'] = 'Méthode d\'inscription dans les cours';
$string['configcourseenrolmethod_desc'] = 'La méthode d\'inscription utilisée pour inscrire les enseignants de cours lors de la création/mise à jour des cours.';
$string['configcourseuserkey'] = 'Identifiant interne d\'utilisateur';
$string['configcourseuserkey_desc'] = 'Champ interne moodle pour identifier les utilisateurs (membres de cours), enseignants et enseignants éditeurs.';
$string['configcourseeditingteachersattribute'] = 'Attribut pour les enseignants éditeurs';
$string['configcourseeditingteachersattribute_desc'] = 'Une liste d\'identifiants d\'enseignants éditeurs.';
$string['configcourseteachersattribute'] = 'Attribut pour les enseignants';
$string['configcourseteachersattribute_desc'] = 'Une liste d\'identifiants d\'enseignants.';
$string['configcourseteachersfilter'] = 'Filtre des identifiants enseignants';
$string['configcourseteachersfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible. La valeur capturée doit être un identifiant primaire de l\'enseigant';
$string['configcoursemembershipfilter'] = 'Filtre pour l\'identifiant d\'utilisateur';
$string['configcoursemembershipfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible. La valeur capturée doit être un identifiant primaire de l\'utilisateur (défini dans les paramètres LDAP standard).';
$string['configcoursemembershipdereferenceattribute'] = 'Attribut indirect d\'identifiant utilisateur';
$string['configcoursemembershipdereferenceattribute_desc'] = 'S\'il est défini, l\'identité d\'utilisateur sera recherché dans cet attribut à partir de la valeur de membre (usuellement un DN). Cela est utile lorsque le DN ne contient pas directement la valeur d\'identité dans son format propre.';
$string['configcoursemembershipcohortdetector'] = 'Détecteur de cohorte';
$string['configcoursemembershipcohortdetector_desc'] = 'Une expression régulière appliquée à la valeur du champ qui test si le membre est une cohorte. Si il est non défini, la détection de cohorte n\'aura pas lieu et tous les membres sont des utilisateurs simples.';
$string['configcoursemembershipcohortfilter'] = 'Filtre pour l\'identifiant de cohorte';
$string['configcoursemembershipcohortfilter_desc'] = 'Une expression régulière appliquée à la valeur du champ qui capture le premier sous-motif disponible. La valeur capturée doit être un identifiant primaire d\'une cohorte (défini dans les réglages de l\'entité "cohortes" du synchroniseur ENT.';
$string['configcoursedefaultcategoryidnumber'] = 'Identifiant de la catégorie par défaut';
$string['configcoursedefaultcategoryidnumber_desc'] = 'Si non vide, un cours de catégorie non résolue sera rangé dans cette catégorie.';
$string['configcoursehardcohortunenrol'] = 'Mode de désincription de cohorte';
$string['configcoursehardcohortunenrol_desc'] = 'Ce qu ise passe lorsqu\'une inscription de cohorte disparait du LDAP. En mode "désactivation", les inscri^ptions individueles sont conservées avec toutes les données utilisateur. Seul l\'accès au cours est coupé. En mode "Suppresison", la méthode est supprimé avec toutes ses inscriptions et données utilisateur associées.';
$string['softcohortunenrol'] = 'Désactivation';
$string['hardcohortunenrol'] = 'Suppression';
$string['categorysyntaxcomposite'] = 'Syntaxe composite';
$string['categorysyntaxsimplepath'] = 'Chemin simple';
$string['categorysyntaxdirectid'] = 'Utilise l\'ID interne (pas de création)';
$string['categorysyntaxidnumber'] = 'Utiise le numéro d\'identification (pas de création)';
$string['coursedeleted'] = 'Cours {$a} supprimé.';
$string['coursecreated'] = 'Cours {$a->name} créé sous l\'id {$a->id}.';
$string['coursecreationerror'] = 'ERREUR: La création du cours {$a->name} a échoué avec le code {$a->id}.';
$string['courseupdated'] = 'Cours {$a->fullname} d\'id {$a->id} mis à jour.';

// Structures.

$string['configstructurecity'] = 'Ville';
$string['configstructurecity_desc'] = 'Attribut LDAP de la ville';
$string['configstructurecityfilter'] = 'Filtre LDAP pour la ville';
$string['configstructurecityfilter_desc'] = 'Clause LDAP pour recherche dans le nom de l\'établissement';
$string['configstructurecontext'] = 'Contexte LDAP';
$string['configstructurecontext_desc'] = 'contexte(base DN) LDAP où sont stockées les définitions d\'établissement';
$string['configstructureid'] = 'Identifiant';
$string['configstructureid_desc'] = 'L\'attribut LDAP portant l\'dentifiant unique d\'établissement';
$string['configstructurename'] = 'Nom courant';
$string['configstructurename_desc'] = 'L\'attribut LDAP portant le nom courant de la structure';
$string['configstructurenamefilter'] = 'Filtre LDAP pour le nom';
$string['configstructurenamefilter_desc'] = 'Clause LDAP pour recherche dans le nom de l\'tablissement';
$string['configstructureaddress'] = 'Adresse';
$string['configstructureaddress_desc'] = 'L\'attribut LDAP contenant l\'adresse de la structure';
$string['configstructuregeoloc'] = 'Géolocalisation';
$string['configstructuregeoloc_desc'] = 'L\'attribut LDAP portant les données de géolocalisation de la structure';

// Profile filtered user selector
$string['all'] = 'Tous';
$string['eleve'] = 'Elèves';
$string['enseignant'] = 'Enseignants';
$string['parent'] = 'Parents';
$string['cdt'] = 'Chefs de travaux';
$string['administration'] = 'Administration';
$string['cohort'] = 'Cohorte';
$string['reload'] = 'Recharger le filtre';
$string['allusers'] = 'Tous les utilisateurs';

$string['relocateteachercourses_help'] = '
Déplacer tous les cours appartenant à un seul enseignant (en édition) dans la
catégorie standard de l\'enseignant. Ceci affecte tous les cours de moodle sauf les cours
placés dans des catégories protégées (voir réglages globaux du composant d\'installation ENT).
';

<<<<<<< HEAD
$string['plugindist_desc'] = '
<p>Ce plugin est distribué dans la communauté Moodle pour l\'évaluation de ses fonctions centrales
correspondant à une utilisation courante du plugin. Une version "professionnelle" de ce plugin existe et est distribuée
sous certaines conditions, afin de soutenir l\'effort de développement, amélioration; documentation et suivi des versions.</p>
<p>Contactez un distributeur pour obtenir la version "Pro" et son support.</p>
<p>Notez que les deux composant local_sharedresources et mod_sharedresource doivent fonctionner au même niveau de distribution</p>
<p><a href="http://www.mylearningfactory.com/index.php/documentation/Distributeurs?lang=fr_utf8">Distributeurs MyLF</a></p>';
=======
require(__DIR__.'/pro_additional_strings.php');
>>>>>>> MOODLE_39_STABLE
