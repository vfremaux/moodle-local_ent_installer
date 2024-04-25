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

defined('MOODLE_INTERNAL') || die();

class local_ent_installer_renderer extends plugin_renderer_base {

    public function print_time_report($range, $rangedata) {
        global $OUTPUT;

        $str = $OUTPUT->heading(get_string($range, 'local_ent_installer'));

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

        $timegrid = array(array(array(date('d-M-Y', time()), '0')));
        if ($rangedata) {
            $i = 0;
            $iwo = 0;
            foreach ($rangedata as $b) {
                $sumduration += $b->timerun;
                if ($b->timerun > $maxduration) {
                    $maxduration = $b->timerun;
                }
                if (is_null($minduration)) {
                    $minduration = $b->timerun;
                } else {
                    if ($b->timerun < $minduration) {
                        $minduration = $b->timerun;
                    }
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

        $str .= $OUTPUT->box_start('ent-installer-curve');
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
                'marginBottom' => '30px',
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
                array('color' => '#A000A0'),
            ),
            'cursor' => array(
                'show' => true,
                'zoom' => true,
                'showTooltip' => false
            ),
        );
        $str .= local_vflibs_jqplot_print_graph('plot2', $jqplot, $timegrid, 750, 250, 'margin:20px;', true);

        $str .= '<center>';
        $resetstr = get_string('resetzoom', 'local_ent_installer');
        $str .= '<button id="timegraph-zoom-reset" onclick="plot2.resetZoom();return true;" value="1">'.$resetstr.'</button>';
        $str .= '</center>';
        $str .= $OUTPUT->box_end();

        $str .= $OUTPUT->box_start('ent-installer-report-globals');

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

        $str .= html_writer::table($table);

        $str .= $OUTPUT->box_end();
        return $str;
    }
}