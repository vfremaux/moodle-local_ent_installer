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

/**
 * Time selector
 *
 * This is a liiitle bit messy. we're using two selects, but we're returning
 * them as an array named after $name (so we only use $name2 internally for the setting)
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_configdatetime extends admin_setting {

    /**
     * Get the selected time
     *
     * @return mixed An array containing 'h'=>xx, 'm'=>xx, or null if not set
     */
    public function get_setting() {
        $result = $this->config_read($this->name);

        $datearr = getdate($result);

        $data = array('h' => $datearr['hours'],
            'm' => $datearr['minutes'],
            'y' => $datearr['year'],
            'M' => $datearr['mon'],
            'd' => $datearr['mday']);
        return $data;
    }

    /**
     * Store the time as unix timestamp
     *
     * @param array $data Must be form 'y' => xxxx, 'M' => xx, 'd' => xx, 'h'=>xx, 'm'=>xx
     * @return bool true if success, false if not
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            return '';
        }

        $datetime = mktime($data['h'], $data['m'], 0, $data['M'], $data['d'], $data['y']);

        $result = $this->config_write($this->name, $datetime);
        return ($result ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Returns XHTML time select fields
     *
     * @param array $data Must be form 'h'=>xx, 'm'=>xx
     * @param string $query
     * @return string XHTML time select fields and wrapping div(s)
     */
    public function output_html($data, $query = '') {
        $default = $this->get_defaultsetting();

        if (is_array($default)) {
            $defaultinfo = $default['y'].'-'.$default['M'].'-'.$default['d'].' '.$default['h'].':'.$default['m'];
        } else {
            $defaultinfo = null;
        }

        $return = '<div class="form-datetime defaultsnext">';
        $return .= '<select id="'.$this->get_id().'y" name="'.$this->get_full_name().'[y]">';
        for ($i = 2010; $i < 2030; $i++) {
            $return .= '<option value="'.$i.'"'.($i == $data['y'] ? ' selected="selected"' : '').'>'.$i.'</option>';
        }
        $return .= '</select><select id="'.$this->get_id().'M" name="'.$this->get_full_name().'[M]">';
        for ($i = 1; $i < 12; $i++) {
            $selected = ($i == $data['M'] ? ' selected="selected"' : '');
            $return .= '<option value="'.$i.'"'.$selected.'>'.sprintf('%02d', $i).'</option>';
        }
        $return .= '</select><select id="'.$this->get_id().'d" name="'.$this->get_full_name().'[d]">';
        for ($i = 1; $i < 31; $i++) {
            $selected = ($i == $data['d'] ? ' selected="selected"' : '');
            $return .= '<option value="'.$i.'"'.$selected.'>'.sprintf('%02d', $i).'</option>';
        }
        $return .= '</select><select id="'.$this->get_id().'h" name="'.$this->get_full_name().'[h]">';
        for ($i = 0; $i < 24; $i++) {
            $return .= '<option value="'.$i.'"'.($i == $data['h'] ? ' selected="selected"' : '').'>'.$i.'</option>';
        }
        $return .= '</select>:<select id="'.$this->get_id().'m" name="'.$this->get_full_name().'[m]">';
        for ($i = 0; $i < 60; $i += 5) {
            $return .= '<option value="'.$i.'"'.($i == $data['m'] ? ' selected="selected"' : '').'>'.$i.'</option>';
        }
        $return .= '</select></div>';
        return format_admin_setting($this, $this->visiblename, $return, $this->description, false, '', $defaultinfo, $query);
    }

}

/**
 * Static setting - Has just a fixed value given by default. Non mutable.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_static extends admin_setting {

    protected $staticvalue;

    /**
     * not a setting, just text
     * @param string $name unique ascii name, either 'mysetting' for settings that in config, or 'myplugin/mysetting' for ones in config_plugins.
     * @param string $heading heading
     * @param string $information text in box
     */
    public function __construct($key, $visiblename, $description, $staticvalue) {
        $this->staticvalue = $staticvalue;
        parent::__construct($key, $visiblename, $description, $staticvalue);
    }

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        return $this->config_read($this->name);
    }

    /**
     * Always returns true
     * @return bool Always returns true
     */
    public function get_defaultsetting() {
        return $this->staticvalue;
    }

    /**
     * Never write settings
     * @return string Always returns an empty string
     */
    public function write_setting($data) {
        // Write the forced static value.
        return ($this->config_write($this->name, $this->staticvalue) ? '' : get_string('errorsetting', 'admin'));
    }

    /**
     * Returns an HTML string
     * @return string Returns an HTML string
     */
    public function output_html($data /* unused */, $query='') {
        $default = $this->get_defaultsetting();

        return format_admin_setting($this, $this->visiblename,
        '<div class="form-static defaultsnext"><input type="text" disabled="disabled" name="'.$this->name.'" value="'.s($this->staticvalue).'" /></div>',
        $this->description, true, '', null, $query);
    }
}
