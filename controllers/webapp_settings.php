<?php

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

class Webapp_Settings extends Webapp_Controller
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
        // Load libraries
        //---------------

        $this->lang->load('webapp');
        $this->load->library($this->library);

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('hostname', $this->library, 'validate_hostname');
        $this->form_validation->set_policy('aliases', $this->library, 'validate_aliases');
        // FIXME $this->form_validation->set_policy('directory', $this->library, 'validate_directory');

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $options['hostname'] = $this->input->post('hostname');
            $options['aliases'] = $this->input->post('aliases');

            try {
                $this->webapp_driver->set_basic_options($options);

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
            $data['info'] = $this->webapp_driver->get_info();
            $data['hostname_access'] = $this->webapp_driver->get_hostname_access();
            $data['directory_access'] = $this->webapp_driver->get_directory_access();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('webapp/settings', $data, lang('webapp_web_server_settings'));
    }
}
