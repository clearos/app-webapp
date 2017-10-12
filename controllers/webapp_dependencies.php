<?php

/**
 * Webapp dependencies controller.
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
 * Webapp dependencies controller.
 *
 * @category   apps
 * @package    webapp
 * @subpackage controller
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Dependencies extends ClearOS_Controller
{
    protected $app_basename = NULL;
    protected $driver = NULL;

    /**
     * Webapp version constructor.
     *
     * @param string $app_basename    webapp name
     *
     * @return view
     */

    function __construct($app_basename)
    {
        $this->app_basename = $app_basename;
        $this->driver = $app_basename . '/Webapp_Driver';
    }

    /**
     * Webapp dependencies controller.
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
            $data['readiness'] = $this->webapp_driver->get_readiness();
            $data['webapp'] = $this->app_basename;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('webapp/dependencies', $data, lang('webapp_dependencies'));
    }
}
