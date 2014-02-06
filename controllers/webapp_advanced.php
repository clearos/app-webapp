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
     * @return view
     */

    function index($advanced = FALSE)
    {
        if ($advanced)
            $this->_item('view');
        else
            $this->page->view_form('webapp/advanced_warning', $data, lang('base_advanced_settings'));
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

        $this->form_validation->set_policy('web_access', 'webapp/Webapp', 'validate_web_access', TRUE);
        $this->form_validation->set_policy('require_authentication', 'webapp/Webapp', 'validate_web_require_authentication', TRUE);
        $this->form_validation->set_policy('show_index', 'webapp/Webapp', 'validate_web_show_index', TRUE);
        $this->form_validation->set_policy('follow_symlinks', 'webapp/Webapp', 'validate_web_follow_symlinks', TRUE);
        $this->form_validation->set_policy('ssi', 'webapp/Webapp', 'validate_web_allow_ssi', TRUE);
        $this->form_validation->set_policy('htaccess', 'webapp/Webapp', 'validate_web_htaccess_override', TRUE);
        $this->form_validation->set_policy('php', 'webapp/Webapp', 'validate_web_php', TRUE);
        $this->form_validation->set_policy('cgi', 'webapp/Webapp', 'validate_web_cgi', TRUE);

        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $options['web_access'] = $this->input->post('web_access');
            $options['require_authentication'] = $this->input->post('require_authentication');
            $options['show_index'] = $this->input->post('show_index');
            $options['follow_symlinks'] = $this->input->post('follow_symlinks');
            $options['ssi'] = $this->input->post('ssi');
            $options['htaccess'] = $this->input->post('htaccess');
            $options['php'] = $this->input->post('php');
            $options['cgi'] = $this->input->post('cgi');
            $options['require_ssl'] = FALSE; // Hard code this for now

            try {
                $this->webapp_driver->set_advanced_options($options);
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

            $data['info'] = $this->webapp_driver->get_info();
            $data['accessibility_options'] = $this->webapp_driver->get_web_access_options();
            $data['accessibility_default'] = $this->webapp_driver->get_web_access_default();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        if (! isset($data['info']['WebAccess']))
            $data['info']['WebAccess'] = $data['accessibility_default'];

        if (! isset($data['info']['WebHtaccessOverride']))
            $data['info']['WebHtaccessOverride'] = TRUE;

        if (! isset($data['info']['WebReqSsl']))
            $data['info']['WebReqSsl'] = FALSE;

        if (! isset($data['info']['WebReqAuth']))
            $data['info']['WebReqAuth'] = FALSE;

        if (! isset($data['info']['WebShowIndex']))
            $data['info']['WebShowIndex'] = TRUE;

        if (! isset($data['info']['WebPhp']))
            $data['info']['WebPhp'] = TRUE;

        if (! isset($data['info']['WebCgi']))
            $data['info']['WebCgi'] = FALSE;

        // Load the views
        //---------------

        $this->page->view_form('webapp/advanced', $data, lang('webapp_web_server_settings'));
    }
}
