<?php

/**
 * Webapp site settings controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
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

use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webapp site settings controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Site extends ClearOS_Controller
{
    protected $library = NULL;
    protected $app_basename = NULL;

    /**
     * Webapp server settings constructor.
     *
     * @param string $app_basename web app name
     *
     * @return view
     */

    function __construct($app_basename, $app_description)
    {
        parent::__construct($app_basename);

        $this->app_basename = $app_basename;
        $this->app_description = $app_description;
        $this->library = $app_basename . '/Webapp_Driver';
        $this->site_library = $app_basename . '/Webapp_Site_Driver';
    }

    /**
     * Default controller.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->library);

/*

            $web_server_running_status = $this->joomla->get_web_server_running_status();
            $mariadb_running_status = $this->joomla->get_mariadb_running_status();
            $mariadb_password_status = $this->joomla->get_mariadb_root_password_set_status();
            $joomla_version_not_downloaded = $this->joomla->get_versions(TRUE);
FIXME

        if (($web_server_running_status == 'stopped') ||
            ($mariadb_running_status != 'running')||
            !$mariadb_password_status ||
            !$joomla_version_not_downloaded) {
            $views = array('joomla/dependencies', 'joomla/version');
        } else {

*/


        // Load view data
        //---------------

        try {
            $data['basename'] = $this->app_basename;
            $data['sites'] = $this->webapp_driver->get_sites();
            // $data['is_ready'] = $this->webapp_driver->is_engine_ready();
            $data['base_path'] = 'https://'.$_SERVER['SERVER_ADDR'].'/joomla/'; // FIXME
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('webapp/summary', $data, $this->app_description);
    }

    /**
     * Add view.
     *
     * @return view
     */

    function add()
    {
        $this->_item('add');
    }

    /**
     * Edit view.
     *
     * @return view
     */

    function edit($site)
    {
        $this->_item('edit', $site);
    }

    /**
     * Destroy site.
     *
     * @param string $folder_name Folder Name 
     * @return redirect to index after delete
     */

    function destroy($folder_name)
    {
        // Load dependencies
        //------------------

        $this->lang->load('joomla');
        $this->load->library('joomla/Joomla');

        if ($_POST) {
            $database_name = '';
            $folder_name = $this->input->post('folder_name');
            $delete_database = $this->input->post('delete_database');

            if ($folder_name)
                $database_name = $this->joomla->get_database_name($folder_name);
            $_POST['database_name'] = $database_name;
            $_POST['folder_name'] = $folder_name;
            $this->form_validation->set_policy('folder_name', 'joomla/Joomla', 'validate_folder_name_exists', TRUE);
            if ($delete_database && $database_name) {
                $this->form_validation->set_policy('database_name', 'joomla/Joomla', 'validate_existing_database', TRUE);
                $this->form_validation->set_policy('root_username', 'joomla/Joomla', 'validate_root_username', TRUE);
                $this->form_validation->set_policy('root_password', 'joomla/Joomla', 'validate_root_password', TRUE);
            }
            $form_ok = $this->form_validation->run();

            if ($form_ok) {
                $folder_name = $this->input->post('folder_name');
                $database_name = $this->input->post('database_name');
                $root_username = $this->input->post('root_username');
                $root_password = $this->input->post('root_password');

                try {
                    $this->joomla->delete_folder($folder_name);
                    if ($delete_database && $database_name) {
                        $this->joomla->backup_database($database_name, $root_username, $root_password);
                        $this->joomla->delete_database($database_name, $root_username, $root_password);
                    }
                    $this->page->set_message(lang('joomla_project_delete_success'), 'info');
                    redirect('/joomla');
                } catch (Exception $e) {
                    $this->page->view_exception($e);
                }
            } else {
                $this->page->view_exception(validation_errors());
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Common form.
     *
     * @param string $form_type form type
     * @param string $site site name
     *
     * @return view
     */

    function _item($form_type, $site = '')
    {
        // Load libraries
        //---------------

        $this->lang->load('webapp');
        $this->lang->load('joomla');
        $this->load->library('joomla/Joomla');
        $this->load->library('web_server/Httpd');
        $this->load->factory('groups/Group_Manager_Factory');

        // Set validation rules
        //---------------------

        $use_exisiting_database = $this->input->post('use_exisiting_database');

        $this->form_validation->set_policy('folder_name', 'joomla/Joomla', 'validate_folder_name', TRUE);
        if($use_exisiting_database == "Yes")
            $this->form_validation->set_policy('database_name', 'joomla/Joomla', 'validate_existing_database', TRUE);
        else
            $this->form_validation->set_policy('database_name', 'joomla/Joomla', 'validate_new_database', TRUE);
        $this->form_validation->set_policy('database_user_name', 'joomla/Joomla', 'validate_database_username', TRUE);
        $this->form_validation->set_policy('database_user_password', 'joomla/Joomla', 'validate_database_password', TRUE);
        $this->form_validation->set_policy('root_username', 'joomla/Joomla', 'validate_root_username', TRUE);
        $this->form_validation->set_policy('root_password', 'joomla/Joomla', 'validate_root_password', TRUE);
        $this->form_validation->set_policy('joomla_version', 'joomla/Joomla', 'validate_joomla_version', TRUE);
        $this->form_validation->set_policy('ssl_certificate', 'web_server/Httpd', 'validate_ssl_certificate', TRUE);
        $this->form_validation->set_policy('group', 'web_server/Httpd', 'validate_group', TRUE);

        if (clearos_app_installed('ftp'))
            $this->form_validation->set_policy('ftp', 'web_server/Httpd', 'validate_ftp_state', TRUE);

        if (clearos_app_installed('samba'))
            $this->form_validation->set_policy('file', 'web_server/Httpd', 'validate_file_state', TRUE);

        $form_ok = $this->form_validation->run();

        /*
            $this->form_validation->set_policy('hostname', $this->site_library, 'validate_hostname', TRUE);
            $this->form_validation->set_policy('aliases', $this->site_library, 'validate_aliases');
        */

        // Extra validation
        //-----------------

        /*
            $resolvable = dns_get_record($this->input->post('hostname'));
            if (!$resolvable) {
                $this->form_validation->set_error('hostname', lang('webapp_hostname_does_not_resolve_warning'));
                $form_ok = FALSE;
            }
        */


        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $folder_name = $this->input->post('folder_name');
            $database_name = $this->input->post('database_name');
            $database_username = $this->input->post('database_user_name');
            $database_user_password = $this->input->post('database_user_password');
            $root_username = $this->input->post('root_username');
            $root_password = $this->input->post('root_password');
            $joomla_version = $this->input->post('joomla_version');
            $group = ($this->input->post('group')) ? $this->input->post('group') : '';
            $ftp_state = ($this->input->post('ftp')) ? $this->input->post('ftp') : FALSE;
            $file_state = ($this->input->post('file')) ? $this->input->post('file') : FALSE;
            $ssl_certificate = $this->input->post('ssl_certificate');

            try {
                $this->joomla->add_project($folder_name, $database_name, $database_username, $database_user_password, $root_username, $root_password, $use_exisiting_database, $joomla_version, $group, $ssl_certificate, $ftp_state, $file_state);
                $this->page->set_message(lang('joomla_project_add_success'), 'info');
                redirect('/joomla');

            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['form_type'] = $form_type;

            $this->joomla->check_dependencies();

            $version_all = $this->joomla->get_versions();
            $versions = array();

            foreach ($version_all as $key => $value) {
                if ($value['clearos_path'])
                    $versions[$value['file_name']] = $value['version'];
            }

            $data['versions'] = $versions;
            $data['default_version'] = 'latest.zip';

            $data['ftp_available'] = clearos_app_installed('ftp');
            $data['file_available'] = clearos_app_installed('samba');
            $data['ssl_certificate_options'] = $this->httpd->get_ssl_certificate_options();

            $groups = $this->group_manager->get_details();

            foreach ($groups as $group => $details)
                $data['groups'][$group] = $group . ' - ' . $details['core']['description'];
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the view
        //--------------

        // $this->page->view_form('add_project', $data, lang('joomla_app_name'));

        $this->page->view_form('webapp/site', $data, lang('base_settings'));
    }
}
