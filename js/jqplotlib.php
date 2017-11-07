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

if (!function_exists('require_jqplot_libs')) {

    /**
     *
     *
     */
    function require_jqplot_libs($libroot) {
        global $CFG, $PAGE;
        static $jqplotloaded = false;

        if ($jqplotloaded) {
            return;
        }

        ent_installer_check_jquery();
        $PAGE->requires->js('/local/ent_installer/js/jqplot/jquery.jqplot.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/excanvas.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.dateAxisRenderer.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.barRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.highlighter.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.canvasOverlay.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.cursor.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.pointLabels.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.logAxisRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.canvasTextRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.enhancedLegendRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.pieRenderer.min.js', true);
        $PAGE->requires->js('/local/ent_installer/js/jqplot/plugins/jqplot.donutRenderer.min.js', true);
        $jqplotloaded = true;
    }

    /**
     * prints any JQplot graph type given a php descriptor and dataset
     *
     */
    function jqplot_print_graph($htmlid, $graph, &$data, $width, $height, $addstyle = '', $return = false, $ticks = null) {
        global $plotid;
        static $instance = 0;

        $htmlid = $htmlid.'_'.$instance;
        $instance++;

        $str = "<center><div id=\"$htmlid\" style=\"{$addstyle} width:{$width}px; height:{$height}px;\"></div></center>";
        $str .= "<script type=\"text/javascript\">\n";

        if (!is_null($ticks)){
            $ticksvalues = implode("','", $ticks);
            $str .= "var ticks = ['$ticksvalues']; \n";
        }

        $varsetlist = json_encode($data);
        $varsetlist = preg_replace('/"(\d+)\"/', "$1", $varsetlist);
        $jsongraph = json_encode($graph);
        $jsongraph = preg_replace('/"\$\$\.(.*?)\"/', "$1", $jsongraph);
        $jsongraph = preg_replace('/"(\$\.jqplot.*?)\"/', "$1", $jsongraph);

        $str .= "
        $.jqplot.config.enablePlugins = true;

        plot{$plotid} = $.jqplot(
            '{$htmlid}', 
            $varsetlist, 
            {$jsongraph}
        );
         ";
        $str .= "</script>";

         $plotid++;

         if ($return) {
            return $str;
        }
         echo $str;
    }

    /**
     * TODO : unfinished
     *
     */
    function jqplot_print_vert_bar_graph(&$data, $title, $htmlid) {
        global $plotid;
        static $instance = 0;

        $htmlid = $htmlid.'_'.$instance;
        $instance++;

        echo "<div id=\"$htmlid\" style=\"margin-top:20px; margin-left:20px; width:700px; height:400px;\"></div>";
        echo "<script type=\"text/javascript\" language=\"javascript\">";
        echo "
            $.jqplot.config.enablePlugins = true;
        ";

        $title = addslashes($title);

        $answeredarr = array($data->answered, $data->aanswered, $data->canswered);
        $matchedarr = array($data->matched, $data->amatched, $data->cmatched);
        $hitratioarr = array($data->hitratio * 100, $data->ahitratio * 100, $data->chitratio * 100);

        print_jqplot_barline('answered', $answeredarr);
        print_jqplot_barline('matched', $matchedarr);
        print_jqplot_barline('hitratio', $hitratioarr);
        echo "
            plot{$plotid} = $.jqplot(
                '$htmlid', 
                [$listattempts], 
                { legend:{show:true, location:'ne'},
                title:'$title', 
                seriesDefaults:{ 
                    renderer:$.jqplot.BarRenderer,
                      rendererOptions:{barDirection:'vertical', barPadding: 6, barMargin:15}, 
                      shadowAngle:135
                }, 
                series:[
                ],   
                axesDefaults:{useSeriesColor: true},   
                axes:{ yaxis:{label:'Questions', min:0}, 
                       y2axis:{label:'Hit Ratio', min:0, max:100, tickOptions:{formatString:'%d\%'}}
                }
            });
        ";

        echo "</script>";
        $plotid++;

    }

    /**
    * 
    *
    */
    function jqplot_print_labelled_graph(&$data, $title, $htmlid, $xlabel = '', $ylabel = '') {
        global $plotid;
        static $instance = 0;

        $htmlid = $htmlid.'_'.$instance;
        $instance++;

        echo "<center><div id=\"$htmlid\" style=\"margin-bottom:20px; margin-left:20px; width:480px; height:480px;\"></div></center>";
        echo "<script type=\"text/javascript\" language=\"javascript\">";
        echo "
            $.jqplot.config.enablePlugins = true;
        ";

        $title = addslashes($title);

        print_jqplot_labelled_rawline($data, 'data_'.$htmlid);

        echo "
            plot{$plotid} = $.jqplot(
                '$htmlid',
                [data_$htmlid],
                {
                title:'$title',
                seriesDefaults:{
                    renderer:$.jqplot.LineRenderer,
                      showLine:false,
                      showMarker:true,
                      shadowAngle:135,
                      markerOptions:{size:15, style:'circle'},
                      shadowDepth:2
                }, 
                axes:{ xaxis:{label:'{$xlabel}', min:0, max:100, numberTicks:11, tickOptions:{formatString:'%d\%'}},
                       yaxis:{label:'{$ylabel}', min:0, max:100, numberTicks:11, tickOptions:{formatString:'%d\%'}}
                },
                cursor:{zoom:true, showTooltip:false}
            });
        ";

        echo "</script>";
        $plotid++;

    }

    /**
    * 
    *
    */
    function jqplot_print_simple_bargraph(&$data, $title, $htmlid) {
        global $plotid;
        static $instance = 0;

        $htmlid = $htmlid.'_'.$instance;
        $instance++;

        echo "<center><div id=\"$htmlid\" style=\"margin-bottom:20px; margin-left:20px; width:700px; height:480px;\"></div></center>";
        echo "<script type=\"text/javascript\" language=\"javascript\">";
        echo "
            $.jqplot.config.enablePlugins = true;
        ";

        $title = addslashes($title);

        print_jqplot_simplebarline('data_'.$htmlid, $data);

        echo "

            xticks = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 80, 85, 90, 95, 100];

            plot{$plotid} = $.jqplot(
                '$htmlid',
                [data_$htmlid],
                {
                title:'$title',
                seriesDefaults:{
                    renderer:$.jqplot.BarRenderer,
                    rendererOptions:{barPadding: 6, barMargin:4}
                }, 
                series:[
                    {color:'#FF0000'}
                ],
                axes:{ xaxis:{renderer:$.jqplot.CategoryAxisRenderer, label:'{$xlabel} (%)', ticks:xticks},
                       yaxis:{label:'{$ylabel}', autoscale:true}
                },
            });
        ";

        echo '</script>';
        $plotid++;

    }

}
