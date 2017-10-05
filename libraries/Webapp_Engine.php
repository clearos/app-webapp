<?php

/**
 * Webapp engine class.
 *
 * @category   apps
 * @package    webapp
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\webapp;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('webapp');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Daemon as Daemon;
use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\mariadb\MariaDB as MariaDB;
use \clearos\apps\web_server\Httpd as Httpd;

clearos_load_library('base/Daemon');
clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('mariadb/MariaDB');
clearos_load_library('web_server/Httpd');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;

clearos_load_library('base/Engine_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webapp engine class.
 *
 * @category   apps
 * @package    webapp
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    const COMMAND_MYSQL = '/usr/bin/mysql';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $webapp = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp constructor.
     *
     * @param string $webapp webapp basename
     */

    function __construct($webapp)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->webapp = $webapp;
    }

    /**
     * Returns dependency issues.
     *
     * The following factors need to be reviewed for readiness:
     * - state of the web server
     * - state of the database server
     * - password initialization for database server
     *
     * @return array list of issues
     */

    function get_dependency_issues()
    {
        clearos_profile(__METHOD__, __LINE__);

        $web_server = new Httpd();
        $web_server_running_status = $web_server->get_status();

        $mariadb = new MariaDB();
        $mariadb_running_status = $mariadb->get_status();
        $mariadb_password_not_set = $mariadb->is_root_password_set();

        $issues = [];

        if ($web_server_running_status == Daemon::STATUS_STOPPED)
            $issues[] = lang('webapp_web_server_not_running');

        if ($mariadb_running_status == Daemon::STATUS_STOPPED)
            $issues[] = lang('webapp_mariadb_server_not_running');

        if (!$mariadb_password_not_set)
            $issues[] = lang('webapp_mariadb_password_not_set');

        return $issues;
    }

    /**
     * List of sites.
     *
     * @return array $list of all sites for given webapp
     */

    function get_sites()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();
        $webapps = $httpd->get_webapps();

        $list = array();

        foreach ($webapps as $key => $value) {
            $list[$key]['name'] = $key;
            // $list[$key]['database'] = $this->get_database_name($key);
        }

        return $list;
    }
}

// vim: syntax=php ts=4
