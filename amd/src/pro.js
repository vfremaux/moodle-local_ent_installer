<<<<<<< HEAD
/*
 *
 */
// jshint unused:false, undef:false

define(['jquery', 'core/log', 'core/config'], function($, log, cfg) {

    var entinstallerpro = {

        init: function() {

            $('#id_s_local_ent_installer_licensekey').bind('change', this.check_product_key);
            $('#id_s_local_ent_installer_licensekey').trigger('change');
            log.debug('AMD Pro js initialized for ent_installer system');
=======
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

// jshint unused: true, undef:true

define(['jquery', 'core/log', 'core/config'], function($, log, cfg) {

    var ent_installerpro = {

        component: 'local_ent_installer',
        shortcomponent: 'local_ent_installer',
        componentpath: '/local/ent_installer',

        init: function() {

            var licensekeyid = '#id_s_' + ent_installerpro.component + '_licensekey';
            $(licensekeyid).bind('change', this.check_product_key);
            $(licensekeyid).trigger('change');
            log.debug('AMD Pro js initialized for ' + ent_installerpro.component + ' system');
>>>>>>> MOODLE_39_STABLE
        },

        check_product_key: function() {

<<<<<<< HEAD
=======
            var licensekeyid = '#id_s_' + ent_installerpro.component + '_licensekey';

>>>>>>> MOODLE_39_STABLE
            var that = $(this);

            var productkey = that.val().replace(/-/g, '');
            var payload = productkey.substr(0, 14);
            var crc = productkey.substr(14, 2);

<<<<<<< HEAD
            var calculated = entinstallerpro.checksum(payload);
=======
            var calculated = ent_installerpro.checksum(payload);
>>>>>>> MOODLE_39_STABLE

            var validicon = ' <img src="' + cfg.wwwroot + '/pix/i/valid.png' + '">';
            var cautionicon = ' <img src="' + cfg.wwwroot + '/pix/i/warning.png' + '">';
            var invalidicon = ' <img src="' + cfg.wwwroot + '/pix/i/invalid.png' + '">';
            var waiticon = ' <img src="' + cfg.wwwroot + '/pix/i/ajaxloader.gif' + '">';
<<<<<<< HEAD

            if (crc === calculated) {
                var url = cfg.wwwroot + '/local/ent_installer/pro/ajax/services.php?';
                url += 'what=license';
                url += '&service=check';
                url += '&customerkey=' + that.val();
                url += '&provider=' + $('#id_s_local_ent_installer_licenseprovider').val();

                $('#id_s_local_ent_installer_licensekey + img').remove();
                $('#id_s_local_ent_installer_licensekey').after(waiticon);

                $.get(url, function(data) {
                    if (data.match(/SET OK/)) {
                        $('#id_s_local_ent_installer_licensekey + img').remove();
                        $('#id_s_local_ent_installer_licensekey').after(validicon);
                    } else {
                        $('#id_s_local_ent_installer_licensekey + img').remove();
                        $('#id_s_local_ent_installer_licensekey').after(invalidicon);
                    }
                }, 'html');
            } else {
                $('#id_s_local_ent_installer_licensekey + img').remove();
                $('#id_s_local_ent_installer_licensekey').after(cautionicon);
=======
            var found;

            if (crc === calculated) {
                var url = cfg.wwwroot + '/' + ent_installerpro.componentpath + '/pro/ajax/services.php?';
                url += 'what=license';
                url += '&service=check';
                url += '&customerkey=' + that.val();
                url += '&provider=' + $('#id_s_' + ent_installerpro.component + '_licenseprovider').val();

                $(licensekeyid + ' + img').remove();
                $(licensekeyid).after(waiticon);

                $.get(url, function(data) {
                    if (data.match(/SET OK/)) {
                        if (found = data.match(/-\d+.*$/)) {
                            $(licensekeyid + ' + img').remove();
                            $(licensekeyid).after(cautionicon);
                        } else {
                            $(licensekeyid + ' + img').remove();
                            $(licensekeyid).after(validicon);
                        }
                    } else {
                        $(licensekeyid + ' + img').remove();
                        $(licensekeyid).after(invalidicon);
                    }
                }, 'html');
            } else {
                $(licensekeyid + ' + img').remove();
                $(licensekeyid).after(cautionicon);
>>>>>>> MOODLE_39_STABLE
            }
        },

        /**
         * Calculates a checksum on 2 chars.
         */
        checksum: function(keypayload) {

            var crcrange = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            var crcrangearr = crcrange.split('');
            var crccount = crcrangearr.length;
            var chars = keypayload.split('');
            var crc = 0;

            for (var ch in chars) {
                var ord = chars[ch].charCodeAt(0);
                crc += ord;
            }

            var crc2 = Math.floor(crc / crccount) % crccount;
            var crc1 = crc % crccount;
            return '' + crcrangearr[crc1] + crcrangearr[crc2];
        }
    };

<<<<<<< HEAD
    return entinstallerpro;
=======
    return ent_installerpro;
>>>>>>> MOODLE_39_STABLE
});