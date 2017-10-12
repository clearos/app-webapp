<?php

/**
 * Webapp backup controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controller
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
 * Webapp backup controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controller
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Backup extends ClearOS_Controller
{
    protected $app_basename = NULL;
    protected $app_description = NULL;
    protected $backup_driver = NULL;

    /**
     * Webapp backup constructor.
     *
     * @param string $app_basename    webapp name
     * @param string $app_description webapp description
     *
     * @return view
     */

    function __construct($app_basename, $app_description)
    {
        $this->app_basename = $app_basename;
        $this->app_description = $app_description;
        $this->backup_driver = $app_basename . '/Webapp_Backup_Driver';
    }

    /**
     * Webapp backup default controller.
     *
     * @return view
     */

    function index()
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->backup_driver);

        // Load view data
        //---------------

        try {
            $data['backups'] = $this->webapp_backup_driver->get_list();
            $data['webapp'] = $this->app_basename;
            $data['webapp_description'] = $this->app_description;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('webapp/backups', $data, lang('webapp_backups'));
    }

    /**
     * Download backup file.
     *
     * @param string $file_name backup file name
     * @return Start dorce download 
     */ 
    function download($file_name)
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->backup_driver);

        // Load view data
        //---------------

        try {
            $file_path = $this->webapp_backup_driver->get_path($file_name);
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Return file
        //------------

        // Getting file extension.
        $extension = explode('.', $file_name);
        $extension = $extension[count($extension)-1];
        // For Gecko browsers
        header('Content-Transfer-Encoding: binary');
        // Supports for download resume
        header('Accept-Ranges: bytes');
        // Calculate File size
        header('Content-Length: ' . filesize($file_path));
        header('Content-Encoding: none');
        // Change the mime type if the file is not PDF
        header('Content-Type: application/'.$extension);
        // Make the browser display the Save As dialog
        header('Content-Disposition: attachment; filename=' . $file_name);
        readfile($file_path);
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
        $confirm_uri = '/app/' . $this->app_basename . '/backup/destroy/' . $file_name;
        $cancel_uri = '/app/' . $this->app_basename . '/backup/index';
        $items = array($file_name);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroy backup.
     *
     * @param @string $file_name file name
     *
     * @return @rediret load backup index page
     */

    function destroy($file_name)
    {
        // Load dependencies
        //------------------

        $this->lang->load($this->app_basename);
        $this->load->library($this->backup_driver);

        // Load view data
        //---------------

        try {
            $file_path = $this->webapp_backup_driver->delete($file_name);
            $this->page->set_status_deleted();
            redirect('/' . $this->app_basename . '/backup/index');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }
}
