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

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp version constructor.
     *
     * @param string $webapp webapp basename
     * @param string $path   webapp version path
     */

    function __construct($webapp, $path)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->webapp = $webapp;
        $this->path = $path;
    }

    /**
     * Delete downloaded webapp version.
     *
     * @param string $file_name file name
     *
     * @return TRUE if delete completed
     * @throws Engine_Exception
     */

    public function delete($file_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $path_file = $this->path . '/' . $file_name;

        $file = new File($path_file, TRUE);

        if (!$file->exists())
           return FALSE;

        $file->delete();

        return TRUE;
    }

    /**
     * Downloads webapp install file from given location.
     *
     * @param string $version_file_name version file name
     *
     * @return TRUE if download completed
     * @throws Engine_Exception
     */

    public function download($version_file_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $path_file = $this->path . '/' . $version_file_name;
        $file = new File($path_file, TRUE);

        if ($file->exists())
           return;

        $versions = $this->get_versions();
        $download_url = '';

        foreach ($versions as $key => $value) {
            if ($value['file_name'] == $version_file_name) {
                $download_url = $value['download_url'];
                break;
            }
        }

        $shell = new Shell();
        $params = "'$download_url' -P '$this->path'";

        $retval = $shell->execute(self::COMMAND_WGET, $params, FALSE);

        return TRUE;
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S                                 //
    ///////////////////////////////////////////////////////////////////////////

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

        $versions = $this->get_versions();

        foreach ($versions as $details) {
            if ($details['version'] == $version) {
                if ($only_downloaded && empty($details['clearos_path']))
                    return lang('webapp_version_invalid');
                else
                    return;
            }
        }

        return lang('webapp_version_invalid');
    }
    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Returns local system download path.
     * 
     * @param @string $version_name version name 
     *
     * @return @string path to downloaded version file if available
     * @throws Engine_Exception
     */

    protected function _get_version_downloaded_path($version_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $filename = $this->path . '/' . $version_name;
        $file = new File($filename, TRUE);

        if ($file->exists())
            return $filename;

        return FALSE;
    }

    /**
     * Return update version data.
     * 
     * @param array $versions base version array
     *
     * @return array updated version array
     * @throws Engine_Exception
     */

    protected function _update_version_data($versions)
    {
        clearos_profile(__METHOD__, __LINE__);

        foreach ($versions as $key => $value) {
            $versions[$key]['file_name'] = basename($versions[$key]['download_url']);
            $versions[$key]['clearos_path'] = $this->_get_version_downloaded_path(basename($versions[$key]['download_url']));

            if ($only_downloaded) {
                if (!$versions[$key]['clearos_path'])
                    unset($versions[$key]);
            }
        }

        return $versions;
    }
}

// vim: syntax=php ts=4
