<?php

echo 'Subs instances';
echo '<br/>';

$cats = array(
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_admin_cat' => array(
        'name' => 'Administration moodle',
        'idnumber' => 'ADMIN'
    ),
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_archive_cat' => array(
        'name' => 'Corbeille',
        'visible' => false,
        'idnumber' => 'ARCHIVE'
    ),
    'local_ent_installer/teacher_stub_category' => array(
        'name' => 'Espaces enseignants',
        'idnumber' => 'ACADEMIC'
    ),
    'local_coursetemplates/templatecategory' => array(
        'name' => 'Gabarits',
        'idnumber' => 'TEMPLATES'
     ),
    'block_publishflow/deploycategory' => array(
        'name' => 'Arrivées',
        'idnumber' => 'ARRIVALS'
    ),
    'block_publishflow/closedcategory' => array(
        'name' => 'Corbeille',
        'visible' => false,
        'idnumber' => 'ARCHIVE'
    ),
);

$json = json_encode($cats);
echo '<textarea cols="80" rows="20">'.$json.'</textarea>';

echo '<h2>Master instance</h2>';
echo '<br/>';

$cats = array(
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_admin_cat' => array(
        'name' => 'Administration moodle',
        'idnumber' => 'ADMIN'
    ),
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_archive_cat' => array(
        'name' => 'Corbeille',
        'visible' => false,
        'idnumber' => 'ARCHIVE'
    ),
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_shared_area' => array(
        'name' => 'Cours mutualisés',
        'idnumber' => ''
    ),
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_shared_templates' => array(
        'name' => 'Cours mutualisés/Exemples de cours',
        'idnumber' => 'SAMPLES'
    ),
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_shared_courses' => array(
        'name' => 'Cours mutualisés/Cours disponibles',
        'idnumber' => 'SHARED'
    ),
    // Non existant keys will ignore moodle settings bindings.
    'local_ent_installer/foo_shared_workplaces' => array(
        'name' => 'Espaces inter-établissement',
        'idnumber' => 'WORKPLACES'
    ),
    'local_coursetemplates/templatescategory' => array(
        'name' => 'Gabarits',
        'idnumber' => 'TEMPLATES'
     ),
    'block_publishflow/deploycategory' => array(
        'name' => 'Arrivées',
        'idnumber' => 'ARRIVALS'
    ),
    'block_publishflow/closedcategory' => array(
        'name' => 'Corbeille',
        'visible' => false,
        'idnumber' => 'ARCHIVE'
    ),
);

$json = json_encode($cats);
$json = json_encode($cats);
echo '<textarea cols="80" rows="20">'.$json.'</textarea>';
