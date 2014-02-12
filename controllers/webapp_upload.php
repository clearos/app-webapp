<?php

/**
 * Webapp upload settings controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
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
 * Webapp server settings controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Upload extends Webapp_Controller
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
        $this->_item('view');
    }

    /**
     * Edit view.
     *
     * @return view
     */

    function edit()
    {
        $this->_item('edit');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Common form.
     *
     * @param string $form_type form type
     *
     * @return view
     */

    function _item($form_type)
    {
        // Skip upload widget if accounts not initialzed/happy
        //----------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy())
            return;

        // Skip if FTP and File Server not installed 
        //------------------------------------------

        if (!clearos_app_installed('ftp') && !clearos_app_installed('samba'))
            return;

        // Load libraries
        //---------------

        $this->lang->load('webapp');
        $this->load->library($this->library);
        $this->load->factory('groups/Group_Manager_Factory');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('group', $this->library, 'validate_group', TRUE);

        if (clearos_app_installed('ftp'))
            $this->form_validation->set_policy('ftp', $this->library, 'validate_state', TRUE);

        if (clearos_app_installed('samba'))
            $this->form_validation->set_policy('file', $this->library, 'validate_state', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $settings['ftp'] = $this->input->post('ftp');
            $settings['file'] = $this->input->post('file');

            try {
                $this->webapp_driver->set_upload_settings($this->input->post('group'), $settings);

                $this->page->set_status_updated();
                redirect('/' . $this->app_name . '/settings');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['form_type'] = $form_type;
            $data['app_name'] = $this->app_name;

            $data['group'] = $this->webapp_driver->get_group();
            $data['ftp_access'] = $this->webapp_driver->get_ftp_state();
            $data['file_access'] = $this->webapp_driver->get_file_server_state();
            $data['ftp_available'] = clearos_app_installed('ftp');
            $data['file_available'] = clearos_app_installed('samba');

            $groups = $this->group_manager->get_details();

            foreach ($groups as $group => $details)
                $data['groups'][$group] = $group . ' - ' . $details['core']['description'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('webapp/upload', $data, lang('webapp_upload_access'));
    }
}
