<?php

require_once($CFG->libdir.'/formslib.php');

class local_ent_installer_check_runtime_dates_form extends moodleform {
    /**
     * The form definition.
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('header', 'wstestclienthdr', get_string('testclient', 'webservice'));

        // Note: these values are intentionally PARAM_RAW - we want users to test any rubbish as parameters.
        $data = $this->_customdata;
        if ($data['authmethod'] == 'simple') {
            $mform->addElement('text', 'wsusername', 'wsusername');
            $mform->setType('wsusername', PARAM_USERNAME);
            $mform->addElement('text', 'wspassword', 'wspassword');
            $mform->setType('wspassword', PARAM_RAW);
        } else if ($data['authmethod'] == 'token') {
            $mform->addElement('text', 'token', 'token');
            $mform->setType('token', PARAM_RAW_TRIMMED);
        }

        $mform->addElement('hidden', 'authmethod', $data['authmethod']);
        $mform->setType('authmethod', PARAM_ALPHA);

        $settingoptions['sync'] = get_string('wsentsyncdate', 'local_ent_installer');
        $settingoptions['cron'] = get_string('wslastcron', 'local_ent_installer');
        $mform->addElement('select', 'setting', get_string('wssetting', 'local_ent_installer'), $settingoptions);
        $mform->setType('allhosts', PARAM_BOOL);

        $mform->addElement('checkbox', 'allhosts', get_string('wsallhosts', 'local_ent_installer'));
        $mform->setType('allhosts', PARAM_BOOL);

        $dateformatoptions[0] = 'unix timestamp';
        $dateformatoptions[1] = 'Y-m-d H:i';
        $dateformatoptions[2] = 'd/m/Y H:i';
        $dateformatoptions[3] = 'text';
        $mform->addElement('select', 'dateformat', get_string('wsdateformat', 'local_ent_installer'), $dateformatoptions);
        $mform->setType('dateformat', PARAM_INT);

        $mform->addElement('hidden', 'function');
        $mform->setType('function', PARAM_PLUGIN);

        $mform->addElement('hidden', 'protocol');
        $mform->setType('protocol', PARAM_ALPHA);

        $this->add_action_buttons(true, get_string('execute', 'webservice'));
    }

    /**
     * Get the parameters that the user submitted using the form.
     * @return array|null
     */
    public function get_params() {
        if (!$data = $this->get_data()) {
            return null;
        }
        // Remove unused from form data.
        unset($data->submitbutton);
        unset($data->protocol);
        unset($data->function);
        unset($data->wsusername);
        unset($data->wspassword);
        unset($data->token);
        unset($data->authmethod);

        $params['setting'] = $data->setting;
        $params['allhosts'] = (@$data->allhosts) ? 1 : 0;
        $params['dateformat'] = (@$data->dateformat) ? $data->dateformat : 0;
        return $params;
    }
}