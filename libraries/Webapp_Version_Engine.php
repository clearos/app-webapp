<?php

/**
 * Webapp version engine class.
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
use \clearos\apps\base\Shell as Shell;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Shell');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Webapp version engine class.
 *
 * @category   apps
 * @package    webapp
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Version_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////

    const COMMAND_WGET = '/usr/bin/wget';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $webapp = NULL;
    protected $path = NULL;
    protected $versions = [];

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp version constructor.
     *
     * @param string $webapp webapp basename
     */

    public function __construct($webapp)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Pull in Webapp configuruation.
        include clearos_app_base($webapp) . '/deploy/config.php';

        $this->webapp = $webapp;
        $this->path = $config['version_path'];
        $this->versions = $config['versions'];
    }

    /**
     * Deletes downloaded webapp version.
     *
     * @param string $version version
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete($version)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_version($version, FALSE));

        // Delete
        //-------

        $versions = $this->listing();
        $path_file = $versions[$version]['local_path'];

        $file = new File($path_file, TRUE);

        $file->delete();
    }

    /**
     * Downloads webapp install file.
     *
     * @param string $version version
     *
     * @return void
     * @throws Engine_Exception
     */

    public function download($version)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_version($version, FALSE));

        // Check existence
        //----------------

        $versions = $this->listing();

        $path_file = $versions[$version]['local_path'];

        if (!empty($path_file)) {
            $file = new File($path_file, TRUE);

            if ($file->exists())
               return;
        }

        // Download
        //---------

        $download_url = $versions[$version]['download_url'];

        $shell = new Shell();
        $params = "'$download_url' -P '$this->path'";
        $shell->execute(self::COMMAND_WGET, $params, FALSE);
    }

    /**
     * Returns webapp versions.
     *
     * @param boolean $only_downloaded only return downloaded versions
     *
     * @return @array array of available versions
     */

    public function listing($only_downloaded = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_boolean($only_downloaded));

        // Listing
        //--------

        $versions = $this->versions;

        foreach ($this->versions as $version => $details) {
            $versions[$version]['local_path'] = $this->_get_local_path($details['download_url']);

            if ($only_downloaded) {
                if (!$versions[$version]['local_path'])
                    unset($versions[$version]);
            }
        }

        return $versions;
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S                                 //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for booleans.
     *
     * @param boolean $flag flag
     *
     * @return string error message if flag is invalid
     */

    public function validate_boolean($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($flag))
            return lang('base_parameter_invalid');
    }

    /**
     * Validation routine for version.
     *
     * @param string  $version         version
     * @param boolean $only_downloaded validate against only downloaded versions
     *
     * @return error message if version is invalid
     */

    public function validate_version($version, $only_downloaded = TRUE)
    {
        clearos_profile(__METHOD__, __LINE__);

        $versions = $this->listing();

        if (!array_key_exists($version, $versions))
            return lang('webapp_version_invalid');

        if ($only_downloaded && empty($versions[$version]['local_path']))
            return lang('webapp_version_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns local system download path for given URL.
     * 
     * @param string $download_url download URL
     *
     * @return string local filename
     * @throws Engine_Exception
     */

    protected function _get_local_path($download_url)
    {
        clearos_profile(__METHOD__, __LINE__);

        $basename = basename($download_url);

        $filename = $this->path . '/' . $basename;
        $file = new File($filename, TRUE);

        if ($file->exists())
            return $filename;

        return FALSE;
    }
}

// vim: syntax=php ts=4
