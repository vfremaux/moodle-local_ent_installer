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

require('../../config.php');
require_once($CFG->dirroot.'/local/ent_installer/getid_form.php');
require_once($CFG->dirroot.'/local/ent_installer/ldap/ldaplib.php');
require_once($CFG->dirroot.'/local/ent_installer/locallib.php');
require_once($CFG->dirroot.'/local/vflibs/jqplotlib.php');

$url = new moodle_url('/local/ent_installer/synctimereport.php');
$PAGE->set_url($url);

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);
local_vflibs_require_jqplot_libs();

// Process controller.
$reset = optional_param('reset', 0, PARAM_INT);
if ($reset) {
    $DB->delete_records('local_ent_installer', array());
}

$titlestr = get_string('synctimetitle', 'local_ent_installer');

$PAGE->set_context($systemcontext);
$PAGE->set_heading($titlestr);
$PAGE->set_pagelayout('admin');
$PAGE->navbar->add(get_string('pluginname', 'local_ent_installer'), new moodle_url('/admin/settings.php', array('section' => 'local_ent_installer')));
$PAGE->navbar->add(get_string('syncbench', 'local_ent_installer'));

echo $OUTPUT->header();
echo $OUTPUT->heading($titlestr);

// Three month horizon.

$horizon = time() - DAYSECS * 90;

$sumduration = 0;
$minduration = null;
$maxduration = 0;
$suminserts = 0;
$sumupdates = 0;
$suminserterrors = 0;
$sumupdateerrors = 0;
$overtime = 0;
$meantime = 0;
$normalmeantime = 0;
$sumdurationwovertimes = 0;

$timegrid = array(array(array(date('d-M-Y', time()),'0')));
if ($benchrecs = $DB->get_records_select('local_ent_installer', " timestart > $horizon ")) {
    $i = 0;
    $iwo = 0;
    foreach ($benchrecs as $b) {
        $sumduration += $b->timerun;
        if ($b->timerun > $maxduration) $maxduration = $b->timerun;
        if (is_null($minduration)) {
            $minduration = $b->timerun;
        } else {
            if ($b->timerun < $minduration) $minduration = $b->timerun;
        }
        $suminserts += $b->added;
        $sumupdates += $b->updated;
        $suminserterrors += $b->inserterrors;
        $sumupdateerrors += $b->updateerrors;
        if ($b->timerun > OVERTIME_THRESHOLD) {
            $overtime++;
        } else {
            $iwo++;
            $sumdurationwovertimes += $b->timerun;
        }
        $timegrid[0][] = array(date('d-M-Y', $b->timestart), $b->timerun);
        $i++;
    }
    $meantime = $sumduration / $i;
    $normalmeantime = $sumdurationwovertimes / $iwo;
}

echo $OUTPUT->box_start('ent-installer-curve');
$jqplot = array(
    'title' => array(
        'text' => get_string('syncbench', 'local_ent_installer'),
        'fontSize' => '1.3em',
        'color' => '#000080',
        ),
    'legend' => array(
        'show' => true,
        'location' => 'e',
        'placement' => 'outsideGrid',
        'marginLeft' => '10px',
        'border' => '1px solid #808080',
        'labels' => array(get_string('synctime', 'local_ent_installer')),
    ),
    'axesDefaults' => array('labelRenderer' => '$.jqplot.CanvasAxisLabelRenderer'),
    'axes' => array(
        'xaxis' => array(
            'label' => get_string('day'),
            'renderer' => '$.jqplot.DateAxisRenderer',
            'tickOptions' => array('formatString' => '%b&nbsp;%#d'),
            ),
        'yaxis' => array(
            'autoscale' => true,
            'tickOptions' => array('formatString' => '%.2f'),
            'label' => get_string('seconds'),
            'labelRenderer' => '$.jqplot.CanvasAxisLabelRenderer',
            'labelOptions' => array('angle' => 90)
            )
        ),
    'series' => array(
        array('color' => '#C00000'),
    ),
    'cursor' => array(
        'show' => true,
        'zoom' => true,
        'showTooltip' => false
    ),
);
local_vflibs_jqplot_print_graph('plot1', $jqplot, $timegrid, 750, 250, 'margin:20px;');
echo '<center><button id="timegraph-zoom-reset" onclick="plot.resetZoom();return true;" value="'.get_string('reset', 'local_ent_installer').'"></center>';
echo $OUTPUT->box_end();

echo $OUTPUT->box_start('ent-installer-report-globals');

$table = new html_table();
$table->head = array('', '');
$table->align = array('right', 'left');
$table->size = array('60%', '40%');
$table->colstyles = array('head', 'value');
$table->data[] = array(get_string('inserts', 'local_ent_installer'), $suminserts);
$table->data[] = array(get_string('updates', 'local_ent_installer'), $sumupdates);
$table->data[] = array(get_string('inserterrors', 'local_ent_installer'), $suminserterrors);
$table->data[] = array(get_string('updateerrors', 'local_ent_installer'), $sumupdateerrors);
$table->data[] = array(get_string('overtimes', 'local_ent_installer'), $overtime);
$table->data[] = array(get_string('minduration', 'local_ent_installer'), sprintf('%0.2f', $minduration));
$table->data[] = array(get_string('maxduration', 'local_ent_installer'), sprintf('%0.2f', $maxduration));
$table->data[] = array(get_string('meantime', 'local_ent_installer'), sprintf('%0.2f', $meantime));
$table->data[] = array(get_string('normalmeantime', 'local_ent_installer'), sprintf('%0.2f', $normalmeantime));

echo html_writer::table($table);

echo $OUTPUT->box_end();

echo '<center>';
$url = new moodle_url('/local/ent_installer/synctimereport.php', array('reset' => 1));
echo $OUTPUT->single_button($url, get_string('reset', 'local_ent_installer'));
echo '</center>';

echo $OUTPUT->footer();
