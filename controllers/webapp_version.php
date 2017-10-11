<?php

/**
 * Webapp version controller.
 *
 * @category   Apps
 * @package    webapp
 * @subpackage Controller
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
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webapp version controller.
 *
 * @category   Apps
 * @package    webapp
 * @subpackage Controller
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Version extends ClearOS_Controller
{
    protected $app_basename = NULL;
    protected $app_description = NULL;
    protected $version_driver = NULL;

    /**
     * Webapp version constructor.
     *
     * @param string $app_basename    webapp name
     * @param string $app_description webapp description
     *
     * @return view
     */

    function __construct($app_basename, $app_description)
    {
        parent::__construct($app_basename);

        $this->app_basename = $app_basename;
        $this->app_description = $app_description;
        $this->version_driver = $app_basename . '/Webapp_Version_Driver';
    }

    /**
     * Webapp version default controller.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->version_driver);

        // Load view data
        //---------------

        try {
            $data['versions'] = $this->webapp_version_driver->get_versions();
            $data['webapp'] = $this->app_basename;
            $data['webapp_description'] = $this->app_description;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('webapp/versions', $data, lang('webapp_versions'));
    }

    /**
     * Download version file on local system.
     *
     * @param string $file_name file name
     *
     * @return redirect to index after download 
     */ 

    function download($file_name)
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->version_driver);

        // Load view data
        //---------------

        try {
            $this->webapp_version_driver->download($file_name);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Reload
        //-------

        $this->page->set_message(lang('webapp_version_download_success'), 'info');

        redirect('/' . $this->app_basename . '/version');
    }

    /**
     * Delete version view.
     *
     * @param string $file_name file name
     *
     * @return view
     */

    function delete($file_name)
    {
        $confirm_uri = '/app/' . $this->app_basename . '/version/destroy/' . $file_name;
        $cancel_uri = '/app/' . $this->app_basename;
        $items = array($file_name);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Delete Version file on local system
     *
     * @param string $file_name File Name 
     * @return redirect to index after delete 
     */ 

    function destroy($file_name)
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->version_driver);

        // Load view data
        //---------------

        try {
            $this->webapp_version_driver->delete($file_name);

            $this->page->set_status_deleted();
            redirect('/' . $this->app_basename . '/version');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
