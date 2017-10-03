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

clearos_load_language('network');
clearos_load_language('webapp');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\flexshare\Flexshare as Flexshare;
use \clearos\apps\groups\Group_Factory as Group_Factory;
use \clearos\apps\network\Domain as Domain;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\web_server\Httpd as Httpd;

clearos_load_library('base/Engine');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('flexshare/Flexshare');
clearos_load_library('groups/Group_Factory');
clearos_load_library('network/Domain');
clearos_load_library('network/Iface_Manager');
clearos_load_library('web_server/Httpd');

// Exceptions
//-----------

use \Exception as Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

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

class Webapp_Site_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const PATH_SOURCE = '/usr/share';
    const PATH_INSTALL = '/var/clearos';
    const PATH_ARCHIVE = 'archive';
    const PATH_RELEASES = 'releases';
    const PATH_WEBROOT = 'webroot';
    const PATH_LIVE = 'live';
    const COMMAND_UNZIP = '/usr/bin/unzip';
    const COMMAND_TAR = '/bin/tar';
    const COMMAND_OPENSSL = '/usr/bin/openssl';
    const COMMAND_PATCH = '/usr/bin/patch';
    const DEFAULT_HOSTNAME = 'system.lan';
    const DEFAULT_DOMAIN = 'system.lan';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $webapp = NULL;
    protected $path_source = NULL;
    protected $path_install = NULL;
    protected $config = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp constructor.
     *
     * @param string $webapp webapp basename
     */

    function __construct($webapp, $site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->flexshare = 'webapp-' . $webapp . '-' . $site;
        $this->path_source = self::PATH_SOURCE . '/webapp-' . $webapp;
        $this->path_install = self::PATH_INSTALL . '/' . $webapp;
    }

    /**
     * Returns default configuration information for the webapp.
     *
     * @return array default settings for the webap
     */

    function get_advanced_defaults()
    {
        clearos_profile(__METHOD__, __LINE__);

        $settings['web_access'] = Flexshare::ACCESS_ALL;
        $settings['require_authentication'] = FALSE;
        $settings['show_index'] = TRUE;
        $settings['follow_symlinks'] = FALSE;
        $settings['ssi'] = FALSE;
        $settings['htaccess'] = TRUE;
        $settings['php'] = TRUE;
        $settings['cgi'] = FALSE;

        return $settings;
    }

    /**
     * Returns a list of options for advanced settings.
     *
     * @return array advanced options
     * @throws Engine_Exception
     */

    function get_advanced_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        $options['web_access'] = $flexshare->get_web_access_options();

        return $options;
    }

    /**
     * Returns advanced configuration.
     *
     * @return array settings for the webap
     * @throws Engine_Exception
     */

    function get_advanced_settings()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $settings['web_access'] = $this->config['WebAccess'];
        $settings['require_authentication'] = $this->config['WebReqAuth'];
        $settings['show_index'] = $this->config['WebShowIndex'];
        $settings['follow_symlinks'] = $this->config['WebFollowSymLinks'];
        $settings['ssi'] = $this->config['WebAllowSSI'];
        $settings['htaccess'] = $this->config['WebHtaccessOverride'];
        $settings['php'] = $this->config['WebPhp'];
        $settings['cgi'] = $this->config['WebCgi'];

        return $settings;
    }

    /**
     * Returns database admin URL.
     *
     * @return string database admin URL
     * @throws Engine_Exception
     */

    function get_database_url()
    {
        clearos_profile(__METHOD__, __LINE__);

        return '';
    }

    /**
     * Returns directory name.
     *
     * @return string directory name for web alias
     * @throws Engine_Exception
     */

    function get_directory()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $retval = (empty($this->config['WebDirectoryAlias'])) ? '' : $this->config['WebDirectoryAlias'];

        return $retval;
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

        $retval = (empty($this->config['WebDirectoryAlias'])) ? FALSE : TRUE;

        return $retval;
    }

    /**
     * Returns directory access default.
     *
     * Webapps are expected to override this if desired.
     *
     * @return boolean TRUE if directory access default desired
     * @throws Engine_Exception
     */

    function get_directory_access_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        return TRUE;
    }

    /**
     * Returns state of file server access.
     *
     * @return boolean TRUE if file server access is enabled
     * @throws Engine_Exception
     */

    function get_file_server_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $retval = (empty($this->config['FileEnabled'])) ? FALSE : TRUE;

        return $retval;
    }

    /**
     * Returns state of FTP access.
     *
     * @return boolean TRUE if FTP access is enabled
     * @throws Engine_Exception
     */

    function get_ftp_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $retval = (empty($this->config['FtpEnabled'])) ? FALSE : TRUE;

        return $retval;
    }

    /**
     * Returns group.
     *
     * @return string group name
     * @throws Engine_Exception
     */

    function get_group()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $retval = (empty($this->config['ShareGroup'])) ? '' : $this->config['ShareGroup'];

        return $retval;
    }

    /**
     * Returns home URLs.
     *
     * @return array list of home URLs
     * @throws Engine_Exception
     */

    function get_home_urls()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $urls = array();

        if (!empty($this->config['WebServerName']))
            $urls[] = 'http://' . $this->config['WebServerName'];

        if (!empty($this->config['WebDirectoryAlias']))
            $urls[] = 'http://' . $this->_get_ip_for_url() . $this->config['WebDirectoryAlias'];

        return $urls;
    }

    /**
     * Returns hostname.
     *
     * @return string virtual host hostname
     * @throws Engine_Exception
     */

    function get_hostname()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $retval = (empty($this->config['WebServerName'])) ? '' : $this->config['WebServerName'];

        return $retval;
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

        $retval = (empty($this->config['WebServerName'])) ? FALSE : TRUE;

        return $retval;
    }

    /**
     * Returns hostname access default.
     *
     * @return boolean TRUE if hostname access default desired
     * @throws Engine_Exception
     */

    function get_hostname_access_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        return TRUE;
    }

    /**
     * Returns hostname aliases.
     *
     * @return string virtual host hostname aliases
     * @throws Engine_Exception
     */

    function get_hostname_aliases()
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->_load_config();

        $retval = (empty($this->config['WebServerAlias'])) ? '' : $this->config['WebServerAlias'];

        return $retval;
    }

    /**
     * Returns hostname default..
     *
     * @return string virtual host default hostname
     * @throws Engine_Exception
     */

    function get_hostname_default()
    {
        clearos_profile(__METHOD__, __LINE__);

        $domain = new Domain();

        $nickname = $this->get_nickname();
        $domain = $domain->get_default();

        if (empty($domain))
            $domain = self::DEFAULT_DOMAIN;

        return $nickname  . '.' . $domain;
    }

    /**
     * Returns the available versions.
     *
     * @return array list of available versions
     * @throws Engine_Exception
     */

    function get_versions()
    {
        clearos_profile(__METHOD__, __LINE__);

        $listing = array();
        try {
            $folder = new Folder($this->path_source . '/' . self::PATH_RELEASES);
            $listing = $folder->get_listing();
        } catch(Exception $e) {
            // Not fatal
        }

        rsort($listing);

        return $listing;
    }

    /**
     * Returns state of web server.
     *
     * @return boolean state of web server
     * @throws Engine_Exception
     */

    function is_web_server_running()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_running_state();
    }

    /**
     * Initializes a webapp.
     *
     * @param array $options web server options
     *
     * @return void
     * @throws Engine_Exception
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
            Validation_Exception::is_valid($this->validate_directory($options['directory']));

        // Set path names
        //---------------

        $filestamp = strftime("%Y-%m-%d-%H-%M-%S", time()); 

        $archive_path = $this->path_install . '/' . self::PATH_ARCHIVE . '/' . $filestamp;
        $unpack_path = $this->path_install . '/' . self::PATH_WEBROOT . '/';
        $target_path = $this->path_install . '/' . self::PATH_WEBROOT . '/' . self::PATH_LIVE . '/';
        $source_path = $this->path_source . '/' . self::PATH_RELEASES . '/' . $options['version'];

        // Archive old contents
        //---------------------

        $target_folder = new Folder($target_path, TRUE);

        if ($target_folder->exists())
            $target_folder->move_to($archive_path);

        $folder = new Folder($target_path, TRUE);

        // Grab a list of archives and patches
        //------------------------------------

        $archives = array();
        $patches = array();

        $source_folder = new Folder($source_path);
        $source_listing = $source_folder->get_listing();

        foreach ($source_listing as $listing) {
            if (preg_match('/\.zip$/', $listing))
                $archives[] = $listing;
            else if (preg_match('/\.tar.gz$/', $listing))
                $archives[] = $listing;
            else if (preg_match('/\.patch$/', $listing))
                $patches[] = $listing;
        }

        // Unzip
        //------

        $shell = new Shell();

        foreach ($archives as $archive) {
            if (preg_match('/\.zip$/', $archive)) {
                $shell->execute(self::COMMAND_UNZIP, "'" . $source_path . "/" . $archive . "' -d '$target_path'", TRUE);
            } else if (preg_match('/\.tar.gz$/', $archive)) {

                $folder->create('root', 'root', '0755');
                $shell->execute(self::COMMAND_TAR, "--strip-components=1 -C '$target_path' -xzf '" . $source_path . "/" . $archive . "'", TRUE);
            }
        }

        // Patch
        //------

        $shell = new Shell();

        foreach ($patches as $patch)
            $shell->execute(self::COMMAND_PATCH, "-p1 -d '$target_path' -i '" . $source_path . "/" . $patch . "'", TRUE);

        // Post-initialize hook for Webapp drivers
        //----------------------------------------

        if (method_exists($this, '_post_unpacking_hook'))
            $this->_post_unpacking_hook();

        // Clean up permissions
        //---------------------

        $folder->chown('apache', 'apache', TRUE);
        $folder->chmod('g+rw', TRUE);

        // Flexshare hook
        //----------------

        $this->set_core_settings($options);
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

        $target_path = $this->path_install . '/' . self::PATH_WEBROOT . '/' . self::PATH_LIVE . '/';
        $target_folder = new Folder($target_path, TRUE);

        if (!$target_folder->exists())
            return FALSE;

        $listing = $target_folder->get_listing();

        if (count($listing) === 0)
            return FALSE;
        else
            return TRUE;
    }

    /**
     * Sets advanced settings.
     *
     * @param array $settings web server settings
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function set_advanced_settings($settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_hostname($this->flexshare));

        $flexshare = new Flexshare();

        // Web and Options
        if (isset($settings['web_access']))
            $flexshare->set_web_access($this->flexshare, $settings['web_access']);

        if (isset($settings['require_authentication']))
            $flexshare->set_web_require_authentication($this->flexshare, $settings['require_authentication']);

        if (isset($settings['require_ssl']))
            $flexshare->set_web_require_ssl($this->flexshare, $settings['require_ssl']);

        if (isset($settings['show_index']))
            $flexshare->set_web_show_index($this->flexshare, $settings['show_index']);

        if (isset($settings['follow_symlinks']))
            $flexshare->set_web_follow_symlinks($this->flexshare, $settings['follow_symlinks']);

        if (isset($settings['ssi']))
            $flexshare->set_web_allow_ssi($this->flexshare, $settings['ssi']);

        if (isset($settings['htaccess']))
            $flexshare->set_web_htaccess_override($this->flexshare, $settings['htaccess']);

        if (isset($settings['php']))
            $flexshare->set_web_php($this->flexshare, $settings['php']);

        if (isset($settings['cgi']))
            $flexshare->set_web_cgi($this->flexshare, $settings['cgi']);

        // Globals
        $flexshare->set_share_state($this->flexshare, TRUE);
        $flexshare->update_share($this->flexshare, TRUE);
    }

    /**
     * Sets core settings.
     *
     * @param array $settings access settings
     *
     * @return void
     * @throws Engine_Exception
     */

    function set_core_settings($settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        if (!empty($settings['hostname']))
            Validation_Exception::is_valid($this->validate_hostname($settings['hostname']));

        if (!empty($settings['aliases']))
            Validation_Exception::is_valid($this->validate_aliases($settings['aliases']));

        if (!empty($settings['directory']))
            Validation_Exception::is_valid($this->validate_directory($settings['directory']));

        // Flexshare settings
        //-------------------
        
        // Prepend directory with slash if one does not exist
        if (!empty( $settings['directory']) && !preg_match('/^\//', $settings['directory']))
            $settings['directory'] = '/' . $settings['directory'];

        $flexshare = new Flexshare();

        $flexshare->set_web_server_name($this->flexshare, $settings['hostname']);
        $flexshare->set_web_server_alias($this->flexshare, $settings['aliases']);
        $flexshare->set_web_directory_alias($this->flexshare, $settings['directory']);

        $flexshare->update_share($this->flexshare, TRUE);
    }

    /**
     * Sets upload settings for a webapp.
     *
     * @param string $group    group name
     * @param array  $settings upload settings
     *
     * @return  void
     * @throws  Engine_Exception
     */

    function set_upload_settings($group, $settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_group($group));
        Validation_Exception::is_valid($this->validate_state($settings['ftp']));
        Validation_Exception::is_valid($this->validate_state($settings['file']));

        if ($settings['ftp'] && !clearos_library_installed('ftp/ProFTPd'))
            throw new Validation_Exception(lang('webapp_ftp_access_not_available'));

        if ($settings['file'] && !clearos_library_installed('samba_common/Samba'))
            throw new Validation_Exception(lang('webapp_file_server_access_not_available'));

        $flexshare = new Flexshare();

        $flexshare->set_group($this->flexshare, $group);
        $flexshare->set_ftp_enabled($this->flexshare, $settings['ftp']);
        $flexshare->set_file_enabled($this->flexshare, $settings['file']);

        $flexshare->update_share($this->flexshare, TRUE);
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

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S                                 //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for web access.
     *
     * @param boolean $accessibility web access
     *
     * @return string error message if web access is invalid
     */

    function validate_accessibility($accessibility)
    {
        clearos_profile(__METHOD__, __LINE__);

        $flexshare = new Flexshare();

        return $flexshare->validate_web_access($accessibility);
    }

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
            return lang('webapp_aliases_invalid');
    }

    /**
     * Validation routine for directory.
     *
     * @param string $directory directory
     *
     * @return string error message if directory is invalid
     */

    function validate_directory($directory)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Trim any leading slashes
        $directory = preg_replace('/^\//', '', $directory);

        if (!preg_match('/^[\w\/-]+$/', $directory))
            return lang('webapp_directory_invalid');
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
            return lang('groups_group_name_invalid');
    }

    /**
     * Validation routine for hostname
     *
     * @param string $hostname hostname
     *
     * @return string error message if hostname is invalid
     */

    function validate_hostname($hostname)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match("/^[A-Za-z0-9\.\-_]+$/", $hostname))
            return lang('network_hostname_invalid');
    }

    /**
     * Validation routine for state.
     *
     * @param boolean $state state
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

    ///////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S                                          //
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Generates a random password.
     *
     * @return string random password
     * @throws Engine_Exception
     */

    function _generate_password()
    {
        clearos_profile(__METHOD__, __LINE__);

        $shell = new Shell();
        $shell->execute(self::COMMAND_OPENSSL, 'rand -base64 40', TRUE);

        $password = $shell->get_last_output_line();

        return $password;
    }

    /**
     * Returns IP used for generating URLs.
     *
     * @return string IP for URLs
     * @throws Engine_Exception
     */

    function _get_ip_for_url()
    {
        clearos_profile(__METHOD__, __LINE__);

        // If interactive, use IP from client.  Fall back to LAN IP.
        if (empty($_SERVER['HTTP_HOST'])) {
            $iface_manager = new Iface_Manager();
            $ips = $iface_manager->get_most_trusted_ips();
            $ip = $ips[0];
        } else {
            $ip = preg_replace('/:.*/', '', $_SERVER['HTTP_HOST']);
        }

        return $ip;
    }

    /**
     * Loads configuraion.
     *
     * @return void
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($this->config)) {
            $flexshare = new Flexshare();
            $this->config = $flexshare->get_share($this->flexshare);
        }
    }
}

// vim: syntax=php ts=4
