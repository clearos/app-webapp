<?php

/**
 * Webapp advanced settings controller.
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
 * Webapp advanced settings controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Advanced extends Webapp_Controller
{
    /**
     * Webapp advanced settings constructor.
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
     * @param boolean $advanced show advanced flag
     *
     * @return view
     */

    function index($advanced = FALSE)
    {
        if ($advanced) {
            $this->_item('view');
        } else {
            $data['advanced_url'] = $this->app_name . '/advanced/edit';
            $this->page->view_form('webapp/advanced_warning', $data, lang('base_advanced_settings'));
        }
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

        $this->form_validation->set_policy('web_access', $this->library, 'validate_accessibility', TRUE);
        $this->form_validation->set_policy('require_authentication', $this->library, 'validate_state', TRUE);
        $this->form_validation->set_policy('show_index', $this->library, 'validate_state', TRUE);
        $this->form_validation->set_policy('follow_symlinks', $this->library, 'validate_state', TRUE);
        $this->form_validation->set_policy('ssi', $this->library, 'validate_state', TRUE);
        $this->form_validation->set_policy('htaccess', $this->library, 'validate_state', TRUE);
        $this->form_validation->set_policy('php', $this->library, 'validate_state', TRUE);
        $this->form_validation->set_policy('cgi', $this->library, 'validate_state', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $settings['web_access'] = $this->input->post('web_access');
            $settings['require_authentication'] = $this->input->post('require_authentication');
            $settings['show_index'] = $this->input->post('show_index');
            $settings['follow_symlinks'] = $this->input->post('follow_symlinks');
            $settings['ssi'] = $this->input->post('ssi');
            $settings['htaccess'] = $this->input->post('htaccess');
            $settings['php'] = $this->input->post('php');
            $settings['cgi'] = $this->input->post('cgi');
            $settings['require_ssl'] = FALSE; // Hard code this for now

            try {
                $this->webapp_driver->set_advanced_settings($settings);
                $this->page->set_status_updated();
                redirect('/' . $this->app_name . '/advanced');
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

            $defaults = $this->webapp_driver->get_advanced_defaults();
            $data['options'] = $this->webapp_driver->get_advanced_options();
            $data['settings'] = $this->webapp_driver->get_advanced_settings();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Defaults if not set
        //--------------------

        foreach ($defaults as $key => $value) {
            if (!isset($data['settings'][$key]))
                $data['settings'][$key] = $defaults[$key];
        }

        // Load the views
        //---------------

        $this->page->view_form('webapp/advanced', $data, lang('base_advanced_settings'));
    }
}
