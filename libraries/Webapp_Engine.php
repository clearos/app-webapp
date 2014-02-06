<?php

/**
 * Webapp engine class.
 *
 * @category   apps
 * @package    webapp
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013-2014 ClearFoundation
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
clearos_load_language('web_server');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\flexshare\Flexshare as Flexshare;
use \clearos\apps\groups\Group_Factory as Group_Factory;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('flexshare/Flexshare');
clearos_load_library('groups/Group_Factory');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\File_No_Match_Exception as File_No_Match_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;
use \clearos\apps\flexshare\Flexshare_Not_Found_Exception as Flexshare_Not_Found_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/File_No_Match_Exception');
clearos_load_library('base/Validation_Exception');
clearos_load_library('flexshare/Flexshare_Not_Found_Exception');

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
 * @copyright  2013-2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const PATH_BASE = '/usr/share';
    const PATH_ARCHIVE = 'archive';
    const PATH_RELEASES = 'releases';
    const PATH_WEBROOT = 'webroot';
    const PATH_LIVE = 'live';
    const COMMAND_UNZIP = '/usr/bin/unzip';

    /*
    const TYPE_WEB_APP = 'web_app';
    const TYPE_WEB_SITE = 'web_site';
    const TYPE_WEB_SITE_DEFAULT = 'web_site_default';
    */

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $webapp = NULL;
    protected $path = NULL;
    protected $config = array();

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

        $this->flexshare = 'webapp-' . $webapp;
        $this->path = self::PATH_BASE . '/webapp-' . $webapp;
    }

    /**
     * Returns directory access.
     *
     * @return boolean TRUE if directory access is enabled
     * @throws Engine_Exception
     */

    function get_directory_access()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        if (empty($this->config['WebDirectoryAlias']))
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Returns hostname access.
     *
     * @return boolean TRUE if hostname access is enabled
     * @throws Engine_Exception
     */

    function get_hostname_access()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        if (empty($this->config['WebServerName']))
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Returns configuration information for the webapp.
     *
     * @return array settings for the webap
     * @throws Engine_Exception
     */

    function get_info()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        return $this->config;
    }

    /**
     * Returns the available versions.
     *
     * @return array list of available versions
     */

    function get_versions()
    {
        clearos_profile(__METHOD__, __LINE__);

        $folder = new Folder($this->path . '/' . self::PATH_RELEASES);
        $listing = $folder->get_listing();
        rsort($listing);

        return $listing;
    }

    /**
     * Returns the default web access option.
     *
     * @return string
     */

    function get_web_access_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        return Flexshare::ACCESS_ALL;
    }

    /**
     * Returns a list of valid web access options for a flexshare.
     *
     * @return array
     */

    function get_web_access_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->get_web_access_options();
    }

    /**
     * Initializes a webapp.
     *
     * @param array $options web server options
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function initialize($options)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_version($options['version']));

        if (!empty($options['hostname']))
            Validation_Exception::is_valid($this->validate_hostname($options['hostname']));

        if (!empty($options['directory']))
            Validation_Exception::is_valid($this->validate_web_directory_alias($options['directory']));

        // Webapp unpacking
        //-----------------

        $filestamp = strftime("%Y-%m-%d-%H-%M-%S", time()); 

        $archive_path = $this->path . '/' . self::PATH_ARCHIVE . '/' . $filestamp;
        $target_path = $this->path . '/' . self::PATH_WEBROOT . '/' . self::PATH_LIVE . '/';
        $source_path = $this->path . '/' . self::PATH_RELEASES . '/' . $options['version'];
        $source_folder = new Folder($source_path);
        $source_listing = $source_folder->get_listing();

        if (count($source_listing) !== 1)
            throw new Engine_Exception('Too many release files'); // TODO: handle multiple zips?

        // Archive old contents
        $target_folder = new Folder($target_path);

        if ($target_folder->exists())
            $target_folder->move_to($archive_path);

        if (preg_match('/\.zip$/', $source_listing[0])) {
            $shell = new Shell();
            $shell->execute(self::COMMAND_UNZIP, "'" . $source_path . "/" . $source_listing[0] . "' -d '$target_path'", TRUE);

            $target_folder->chown('apache', 'apache', TRUE);
        }

        // Flexshare hook
        //----------------

        $this->set_basic_options($options);
    }

    /**
     * Returns state of initialization.
     *
     * @return boolean TRUE if initialized
     * @throws Engine_Exception
     */

    function is_initialized()
    {
        clearos_profile(__METHOD__, __LINE__);

        $target_path = $this->path . '/' . self::PATH_WEBROOT . '/' . self::PATH_LIVE . '/';
        $target_folder = new Folder($target_path);

        if (!$target_folder->exists())
            return FALSE;

        $listing = $target_folder->get_listing();

        if (count($listing) === 0)
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Sets basic options for a webapp.
     *
     * @param array $options web server options
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function set_basic_options($options)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        if (!empty($options['hostname']))
            Validation_Exception::is_valid($this->validate_hostname($options['hostname']));

        if (!empty($options['aliases']))
            Validation_Exception::is_valid($this->validate_aliases($options['aliases']));

        if (!empty($options['directory']))
            Validation_Exception::is_valid($this->validate_web_directory_alias($options['directory']));

        // Flexshare settings
        //-------------------

        $flexshare = new Flexshare();

        // Server name and aliases
        if (isset($options['hostname']))
            $flexshare->set_web_server_name($this->flexshare, $options['hostname']);

        if (isset($options['aliases']))
            $flexshare->set_web_server_alias($this->flexshare, $options['aliases']);

        if (isset($options['directory']))
            $flexshare->set_web_directory_alias($this->flexshare, $options['directory']);

        $flexshare->update_share($this->flexshare, TRUE);
    }

    /**
     * Sets advanced options for a webapp
     *
     * @param array $options web server options
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function set_advanced_options($options)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($this->flexshare));

        $flexshare = new Flexshare();

        // Web and Options
        if (isset($options['web_access']))
            $flexshare->set_web_access($this->flexshare, $options['web_access']);

        if (isset($options['require_authentication']))
            $flexshare->set_web_require_authentication($this->flexshare, $options['require_authentication']);

        if (isset($options['require_ssl']))
            $flexshare->set_web_require_ssl($this->flexshare, $options['require_ssl']);

        if (isset($options['show_index']))
            $flexshare->set_web_show_index($this->flexshare, $options['show_index']);

        if (isset($options['follow_symlinks']))
            $flexshare->set_web_follow_symlinks($this->flexshare, $options['follow_symlinks']);

        if (isset($options['ssi']))
            $flexshare->set_web_allow_ssi($this->flexshare, $options['ssi']);

        if (isset($options['htaccess']))
            $flexshare->set_web_htaccess_override($this->flexshare, $options['htaccess']);

        if (isset($options['php']))
            $flexshare->set_web_php($this->flexshare, $options['php']);

        if (isset($options['cgi']))
            $flexshare->set_web_cgi($this->flexshare, $options['cgi']);

        // Globals
        $flexshare->set_share_state($this->flexshare, TRUE);
        $flexshare->update_share($this->flexshare, TRUE);
    }

    /**
     * Sets upload options for a webapp.
     *
     * @param string $group   group name
     * @param array  $options upload options
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function set_upload($group, $options)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($this->flexshare));
        Validation_Exception::is_valid($this->validate_group($group));
        Validation_Exception::is_valid($this->validate_ftp_state($options['ftp']));
        Validation_Exception::is_valid($this->validate_file_state($options['file']));

        if ($options['ftp'] && !clearos_library_installed('ftp/ProFTPd'))
            throw new Validation_Exception('web_server_ftp_upload_is_not_available');

        if ($options['file'] && !clearos_library_installed('samba_common/Samba'))
            throw new Validation_Exception('web_server_file_upload_is_not_available');

        $flexshare = new Flexshare();

        $flexshare->set_group($this->flexshare, $group);
        $flexshare->set_ftp_enabled($this->flexshare, $options['ftp']);
        $flexshare->set_file_enabled($this->flexshare, $options['file']);

        $flexshare->update_share($this->flexshare, TRUE);
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S                                 //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for aliases.
     *
     * @param string $aliases aliases
     *
     * @return error message if aliases is invalid
     */

    function validate_aliases($aliases)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($aliases && (!preg_match("/^([0-9a-zA-Z\.\-_ ,\*]+)$/", $aliases)))
            return lang('web_server_aliases_invalid');
    }

    /**
     * Validation routine for flle server state.
     *
     * @param string $state state
     *
     * @return error message if file server state is invalid
     */

    function validate_file_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('web_server_file_server_state_invalid');
    }

    /**
     * Validation routine for FTP state.
     *
     * @param string $state state
     *
     * @return error message if FTP state is invalid
     */

    function validate_ftp_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($state))
            return lang('web_server_ftp_state_invalid');
    }

    /**
     * Validation routine for group.
     *
     * @param string $group_name group name
     *
     * @return error message if group is invalid
     */

    function validate_group($group_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group = Group_Factory::create($group_name);

        if (! $group->exists())
            return lang('web_server_group_invalid');
    }

    /**
     * Validation routine for hostname
     *
     * @param string $hostname server name
     *
     * @return string error message if server name is invalid
     */

    function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[A-Za-z0-9\.\-_]+$/", $hostname))
            return lang('web_server_hostane_invalid');
    }

    /**
     * Validation routine for state.
     *
     * @param boolean $state
     *
     * @return error message if state is invalid
     */

    function validate_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!clearos_is_valid_boolean($state))
            return lang('base_parameter_invalid');
    }

    /**
     * Validation routine for version.
     *
     * @param string $version version
     *
     * @return error message if version is invalid
     */

    function validate_version($version)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! in_array($version, $this->get_versions()))
            return lang('webapp_version_invalid');
    }

    /**
     * Validation routine for web access.
     *
     * @param boolean $accessibility Web access
     *
     * @return string error message if web access is invalid
     */

    function validate_web_access($accessibility)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_access($accessibility);
    }

    /**
     * Validation routine for web allow SSI.
     *
     * @param boolean $state state
     *
     * @return string error message if web allow SSI is invalid
     */

    function validate_web_allow_ssi($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_allow_ssi($state);
    }

    /**
     * Validation routine for CGI state.
     *
     * @param boolean $state state
     *
     * @return string error message if CGI state is invalid
     */

    function validate_web_cgi($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_cgi($state);
    }

    /**
     * Validation routine for CGI state.
     *
     * @param boolean $state state
     *
     * @return string error message if CGI state is invalid
     */

    function validate_web_directory_alias($alias)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Trim any leading slashes
        $alias = preg_replace('/^\//', '', $alias);

        if (!preg_match('/^[\w\/-]+$/', $alias))
            return lang('webapp_directory_invalid');
    }

    /**
     * Validation routine for web follow symlinks.
     *
     * @param boolean $state state
     *
     * @return string error message if web follow symlinks is invalid
     */

    function validate_web_follow_symlinks($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_follow_symlinks($state);
    }

    /**
     * Validation routine for htaccess override.
     *
     * @param boolean $state state
     *
     * @return string error message if htaccess override is invalid
     */

    function validate_web_htaccess_override($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_htaccess_override($state);
    }

    /**
     * Validation routine for PHP state.
     *
     * @param boolean $state state
     *
     * @return string error message if PHP state is invalid
     */

    function validate_web_php($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_php($state);
    }

    /**
     * Validation routine for require authentication.
     *
     * @param boolean $state state
     *
     * @return string error message if require authentication is invalid
     */

    function validate_web_require_authentication($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_require_authentication($state);
    }

    /**
     * Validation routine for web show index.
     *
     * @param boolean $state state
     *
     * @return string error message if web show index is invalid
     */

    function validate_web_show_index($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_show_index($state);
    }

    ///////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S                                          //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuraion.
     *
     * @return void
     */

    function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($this->config)) {
            $flexshare = new Flexshare();
            $this->config = $flexshare->get_share($this->flexshare);
        }
    }
}

// vim: syntax=php ts=4
