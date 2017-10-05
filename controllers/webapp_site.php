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
    protected $driver = NULL;
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
        $this->site_driver = $app_basename . '/Webapp_Site_Driver';
        $this->driver = $app_basename . '/Webapp_Driver';
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
        $this->load->library($this->driver);

        // Load view data
        //---------------

        try {
            $data['webapp'] = $this->app_basename;
            $data['sites'] = $this->webapp_driver->get_sites();
            $data['dep_issues'] = $this->webapp_driver->get_dependency_issues();
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
     * @param string $site site
     * @return redirect to index after delete
     */

    function destroy($site)
    {
        // Load dependencies
        //------------------

        $this->lang->load('webapp');
        $this->load->library($this->site_driver, $site);

        // FIXME: continue conversion

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
                $this->form_validation->set_policy('database_admin_username', 'joomla/Joomla', 'validate_database_admin_username', TRUE);
                $this->form_validation->set_policy('database_admin_password', 'joomla/Joomla', 'validate_database_admin_password', TRUE);
            }
            $form_ok = $this->form_validation->run();

            if ($form_ok) {
                $folder_name = $this->input->post('folder_name');
                $database_name = $this->input->post('database_name');
                $database_admin_username = $this->input->post('database_admin_username');
                $database_admin_password = $this->input->post('database_admin_password');

                try {
                    $this->joomla->delete_folder($folder_name);
                    if ($delete_database && $database_name) {
                        $this->joomla->backup_database($database_name, $database_admin_username, $database_admin_password);
                        $this->joomla->delete_database($database_name, $database_admin_username, $database_admin_password);
                    }

                    $this->page->set_status_deleted();
                    redirect('/' . $this->app_basename);
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
        $this->load->library($this->driver);
        $this->load->library($this->site_driver, $site);

        // Set validation rules
        //---------------------

        if ($form_type == 'add') {
            $use_existing_database = $this->input->post('use_existing_database');

            if($use_existing_database == 'Yes')
                $this->form_validation->set_policy('database_name', $this->driver, 'validate_existing_database', TRUE);
            else
                $this->form_validation->set_policy('database_name', $this->driver, 'validate_new_database', TRUE);

            $this->form_validation->set_policy('database_username', $this->driver, 'validate_database_username', TRUE);
            $this->form_validation->set_policy('database_password', $this->driver, 'validate_database_password', TRUE);
            $this->form_validation->set_policy('database_admin_username', $this->driver, 'validate_database_admin_username', TRUE);
            $this->form_validation->set_policy('database_admin_password', $this->driver, 'validate_database_admin_password', TRUE);
            $this->form_validation->set_policy('webapp_version', $this->driver, 'validate_webapp_version', TRUE);
        }

        $check_exists = ($form_type === 'add') ? TRUE : FALSE; // FIXME - enable this

        $this->form_validation->set_policy('site', $this->site_driver, 'validate_site', TRUE, $check_exists);
        $this->form_validation->set_policy('aliases', $this->site_driver, 'validate_aliases');
        $this->form_validation->set_policy('ssl_certificate', $this->site_driver, 'validate_ssl_certificate', TRUE);
        $this->form_validation->set_policy('group', $this->site_driver, 'validate_group', TRUE);

        if (clearos_app_installed('ftp'))
            $this->form_validation->set_policy('ftp', $this->site_driver, 'validate_ftp_state', TRUE);

        if (clearos_app_installed('samba'))
            $this->form_validation->set_policy('file', $this->site_driver, 'validate_file_state', TRUE);

        $form_ok = $this->form_validation->run();

        // Extra validation
        //-----------------

        if ($this->input->post('submit') && $this->input->post('site')) {
            $resolvable = dns_get_record($this->input->post('site'));
            if (!$resolvable) {
                $this->form_validation->set_error('site', lang('webapp_hostname_does_not_resolve_warning'));
                $form_ok = FALSE;
            }
        }

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && ($form_ok === TRUE)) {
            $group = ($this->input->post('group')) ? $this->input->post('group') : '';
            $ftp_state = ($this->input->post('ftp')) ? $this->input->post('ftp') : FALSE;
            $file_state = ($this->input->post('file')) ? $this->input->post('file') : FALSE;
            $use_existing_db = ($this->input->post('use_existing_database') == 'Yes') ? TRUE : FALSE;

            try {
                if ($form_type == 'add') {
                    $this->webapp_site_driver->add(
                        $this->input->post('site'),
                        $this->input->post('aliases'),
                        $this->input->post('database_name'),
                        $this->input->post('database_username'),
                        $this->input->post('database_password'),
                        $this->input->post('database_admin_username'),
                        $this->input->post('database_admin_password'),
                        $use_existing_db,
                        $this->input->post('webapp_version'),
                        $this->input->post('ssl_certificate'),
                        $group,
                        $ftp_state,
                        $file_state
                    );

                    $this->page->set_status_added();
                } else {
                    $this->webapp_site_driver->update(
                        $this->input->post('aliases'),
                        $group,
                        $ftp_state,
                        $file_state,
                        $this->input->post('ssl_certificate')
                    );

                    $this->page->set_status_updated();
                }

                redirect('/' . $this->app_basename);
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {

            $version_all = $this->webapp_driver->get_versions(TRUE);
            $versions = array();

            foreach ($version_all as $key => $value) {
                if ($value['clearos_path'])
                    $versions[$value['file_name']] = $value['version'];
            }


            $data['form_type'] = $form_type;
            $data['webapp'] = $this->app_basename;
            $data['site'] = $site;
            $data['versions'] = $versions;
            $data['default_version'] = 'latest.zip';
            $data['ftp_available'] = clearos_app_installed('ftp');
            $data['file_available'] = clearos_app_installed('samba');

            $data['groups'] = $this->webapp_site_driver->get_group_options();
            $data['ssl_certificate_options'] = $this->webapp_site_driver->get_ssl_certificate_options();

            if ($form_type == 'add') {
                $data['ftp_enabled'] = TRUE;
                $data['file_enabled'] = TRUE;
            } else {
                $data['ftp_enabled'] = $this->webapp_site_driver->get_ftp_state();
                $data['file_enabled'] = $this->webapp_site_driver->get_file_state();
                $data['ssl_certificate'] = $this->webapp_site_driver->get_ssl_certificate();
                $data['group'] = $this->webapp_site_driver->get_group();
            }

        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the view
        //--------------

        $options['javascript'] = array(clearos_app_htdocs('webapp') . '/webapp.js.php');

        $this->page->view_form('webapp/site', $data, lang('webapp_site'), $options);
    }
}
