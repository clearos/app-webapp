<?php

/**
 * Webapp backup engine class.
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

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\webapp\Webapp_Site_Engine as Webapp_Site_Engine;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('webapp/Webapp_Site_Engine');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webapp backup engine class.
 *
 * @category   apps
 * @package    webapp
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Backup_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    const COMMAND_MYSQLDUMP = '/usr/bin/mysqldump';
    const COMMAND_ZIP = '/usr/bin/zip';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $webapp = NULL;
    protected $path = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp backup constructor.
     *
     * @param string $webapp webapp basename
     */

    public function __construct($webapp)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Pull in Webapp configuruation.
        include clearos_app_base($webapp) . '/deploy/config.php';

        $this->webapp = $webapp;
        $this->path = $config['backup_path'];
    }

    /**
     * Creates database backup.
     *
     * @param string $database_name database name
     * @param string $username      database username
     * @param string $password      database password
     *
     * @return void
     * @throws Engine_Exception
     */

    public function backup_database($database_name, $username, $password)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        $site = new Webapp_Site_Engine($this->webapp);

        Validation_Exception::is_valid($site->validate_database_name($database_name));
        Validation_Exception::is_valid($site->validate_database_username($username));
        Validation_Exception::is_valid($site->validate_database_password($password));

        // Backup
        //-------

        $sql_file_path = $this->path . '/' . $database_name . '__' . date('Y-m-d-H-i-s') . '.sql';

        $params = " -u'$username' -p'$password' '$database_name' > '$sql_file_path'";

        $shell = new Shell();
        $shell->execute(self::COMMAND_MYSQLDUMP, $params, FALSE);
    }

    /**
     * Creates backup of site folder.
     *
     * @param string $site site name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function backup_site($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        $site_engine = new Webapp_Site_Engine($this->webapp);

        Validation_Exception::is_valid($site_engine->validate_site($site));

        // Backup site
        //------------

        $site_root = $site_engine->get_site_root($site);

        $zip_path = $this->path . '/' . $site . '__' . date('Y-m-d-H-i-s') . '.zip';
        $params = "-r $zip_path $site_root";

        $folder = new Folder($site_root);

        if ($folder->exists()) {
            $shell = new Shell();
            $shell->execute(self::COMMAND_ZIP, $params, TRUE);
        }
    }

    /**
     * Deletes backup from system.
     *
     * @param string $file_name backup file name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete($file_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file_path = $this->path . '/' . $file_name;
        $file = new File($file_path);

        if ($file->exists())
            $file->delete();
        else
            throw new Engine_Exception(lang('base_file_not_found'));
    }

    /**
     * Download action for backup.
     *
     * @param string $file_name backup file name
     *
     * @return download
     * @throws Engine_Exception
     */

    public function get_path($file_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file_path = $this->path . '/' . $file_name;

        $file = new File($file_path, FALSE);

        if (!$file->exists())
            throw new Engine_Exception(lang('base_file_not_found'));

        return $file_path;
    }

    /**
     * Return list of available backups.
     *
     * @return array list of available backups
     * @throws Engine_Exception
     */

    public function get_list()
    {
        clearos_profile(__METHOD__, __LINE__);

        $list = array();
        $folder = new Folder($this->path);

        if ($folder->exists())
            $list = $folder->get_listing(TRUE, TRUE);

        return $list;
    }
}

// vim: syntax=php ts=4
