<?php

/**
 * Webapp initialize controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once 'webapp_controller.php';

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webapp initialize controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Initialize extends Webapp_Controller
{
    /**
     * Webapp server settings constructor.
     *
     * @param string $app_name web app name
     *
     * @return view
     */

    function __construct($app_name)
    {
        parent::__construct($app_name);
    }

    /**
     * Default controller.
     *
     * @return view
     */

    function index()
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy()) {
            $this->status->widget($this->app_name);
            return;
        }

        // Load libraries
        //---------------

        $this->lang->load('webapp');
        $this->load->library($this->library);
        $this->load->library('network/Hostname');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('version', $this->library, 'validate_version');
        $this->form_validation->set_policy('hostname_access', $this->library, 'validate_state');
        $this->form_validation->set_policy('directory_access', $this->library, 'validate_state');

        if ($this->input->post('hostname_access'))
            $this->form_validation->set_policy('hostname', $this->library, 'validate_hostname');

        if ($this->input->post('directory_access'))
            $this->form_validation->set_policy('directory', $this->library, 'validate_directory');

        $form_ok = $this->form_validation->run();

        // Extra validation
        //-----------------

        if ($this->input->post('submit') && $this->input->post('hostname_access')) {
            $resolvable = dns_get_record($this->input->post('hostname'));
            if (!$resolvable) {
                $this->form_validation->set_error('hostname', lang('webapp_hostname_does_not_resolve_warning'));
                $form_ok = FALSE;
            }
        }

        if ($this->input->post('submit') && !$this->input->post('hostname_access') && !$this->input->post('directory_access')) {
            $this->form_validation->set_error('hostname_access', lang('webapp_enable_at_least_one_access_type'));
            $this->form_validation->set_error('directory_access', lang('webapp_enable_at_least_one_access_type'));
            $form_ok = FALSE;
        }

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $options['version'] = $this->input->post('version');
            $options['hostname'] = ($this->input->post('hostname_access')) ? $this->input->post('hostname') : '';
            $options['directory'] = ($this->input->post('directory_access')) ? $this->input->post('directory') : '';

            try {
                $this->webapp_driver->initialize($options);

                $this->page->set_status_updated();
                redirect('/' . $this->app_name . '/initialize/getting_started');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['app_name'] = $this->app_name;
            $data['versions'] = $this->webapp_driver->get_versions();
            $data['hostname_access'] = $this->webapp_driver->get_hostname_access_default();
            $data['directory_access'] = $this->webapp_driver->get_directory_access_default();
            $data['is_web_server_running'] = $this->webapp_driver->is_web_server_running();
            $data['hostname'] = $this->webapp_driver->get_hostname_default();

            $nickname = $this->webapp_driver->get_nickname();
            $internet_hostname = $this->hostname->get_internet_hostname();

            $data['directory'] = '/' . $nickname;
            $data['directory_url'] = $internet_hostname . '/' . $nickname;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('webapp/initialize', $data, lang('base_initialize'));
    }

    /**
     * Getting started view.
     *
     * @return view
     */

    function getting_started()
    {
        // Load libraries
        //---------------

        $this->lang->load('webapp');
        $this->load->library($this->library);

        // Load the view data 
        //------------------- 

        try {
            $data['getting_started'] = $this->webapp_driver->get_getting_started_message();
            $data['getting_started_url'] = $this->webapp_driver->get_getting_started_url();
            $data['skip_url'] = '/app/' . $this->app_name;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('webapp/getting_started', $data, lang('webapp_getting_started'));
    }
}
