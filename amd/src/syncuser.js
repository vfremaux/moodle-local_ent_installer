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
 * Javascript controller for controlling the sections.
 *
 * @module     block_multicourse_navigation/collapse_control
 * @package    block_multicourse_navigation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// jshint unused: true, undef:true
define(['jquery', 'core/config', 'core/log'], function($, config, log) {

    /**
     * SectionControl class.
     *
     * @param {String} selector The selector for the page region containing the actions panel.
     */
    return {

        init: function() {

            // Attach togglestate handler to all handles in page.
            $('#id_filter').on('change', this.refreshlist);
            log.debug('AMD ent installer syncuser initialized');
        },

        refreshlist: function() {

            var params = "filter=" + $('#id_filter').val();

            var url = M.cfg.wwwroot + "/local/ent_installer/ajax/get_users.php?" + params;

            $.get(url, function(data) {

                var select = document.getElementById('id_uid');

                // Clear the old options.
                select.options.length = 0;

                // Load the new options.
                var index = 0;

                var options = $.parseJSON(data);

                for (var name in options) {
                    select.options[index] = new Option(options[name], name);
                    index++;
                }

            }, 'html');
        },
    };

});
