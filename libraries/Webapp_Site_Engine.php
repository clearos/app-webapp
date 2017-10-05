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
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\web_server\Httpd as Httpd;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('web_server/Httpd');

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

    protected $config = array();
    protected $site = NULL;
    protected $webapp = NULL;

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp constructor.
     *
     * @param string $webapp webapp basename
     * @param string $site   site name
     */

    public function __construct($webapp, $site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->webapp = $webapp;
        $this->site = $site;
    }

    /**
     * Returns state of file access.
     *
     * @return boolean TRUE if FTP access is enabled
     * @throws Engine_Exception
     */

    public function get_file_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_file_state($this->site);
    }

    /**
     * Returns state of FTP access.
     *
     * @return boolean TRUE if FTP access is enabled
     * @throws Engine_Exception
     */

    public function get_ftp_state()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_ftp_state($this->site);
    }

    /**
     * Returns group.
     *
     * @return string group name
     * @throws Engine_Exception
     */

    public function get_group()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_group($this->site);
    }

    /**
     * Returns list of available groups.
     *
     * @return array list of available groups
     * @throws Engine_Exception
     */

    public function get_group_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_manager = Group_Manager_Factory::create();

        $raw_groups = $group_manager->get_details();
        $groups = [];

        foreach ($raw_groups as $group => $details)
            $groups[$group] = $group . ' - ' . $details['core']['description'];

        return $groups;
    }

    /**
     * Returns configured SSL certificate.
     *
     * @return string SSL certificate name
     * @throws Engine_Exception
     */

    public function get_ssl_certificate()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_ssl_certificate($this->site);

    }

    /**
     * Returns a list of valid web certificates for site.
     *
     * @return string array list of available certificates
     * @throws Engine_Exception
     */

    public function get_ssl_certificate_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_ssl_certificate_options();
    }

    /**
     * Updates parameters for a site.
     *
     * @param string $aliases     aliases
     * @param string $group       group owner
     * @param string $ftp_state   FTP enabled state
     * @param string $file_state  file enabled state
     * @param string $certificate SSL certificate
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function update($aliases, $group, $ftp_state, $file_state, $certificate)
    {
        clearos_profile(__METHOD__, __LINE__);


        Validation_Exception::is_valid($this->validate_aliases($aliases));
        Validation_Exception::is_valid($this->validate_group($group));
        Validation_Exception::is_valid($this->validate_ftp_state($ftp_state));
        Validation_Exception::is_valid($this->validate_file_state($file_state));
        Validation_Exception::is_valid($this->validate_ssl_certificate($certificate));

        $httpd = new Httpd();

        $httpd->set_webapp(
            $this->site,
            $aliases,
            $group,
            $ftp_state,
            $file_state,
            $certificate
        );
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

    public function validate_aliases($aliases)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->validate_aliases($aliases);
    }

    /**
     * Validate admin username.
     *
     * @param string $username Username
     *
     * @return string error message if exists
     */

    public function validate_database_admin_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([a-z0-9_\-\.\$]+)$/', $username))
            return lang('base_username_invalid');
    }

    /**
     * Validate database admin password.
     *
     * @param string $password Password
     *
     * @return string error message if exists
     */

    public function validate_database_admin_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/.*\S.*/', $password))
            return lang('base_password_is_invalid');
    }

    /**
     * Validate database password.
     *
     * @param string $password Password
     *
     * @return string error message if exists
     */

    public function validate_database_password($password)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/.*\S.*/', $password))
            return lang('base_password_is_invalid');
    }

    /**
     * Validate database username.
     *
     * @param string $username Username
     *
     * @return string error message if exists
     */

    public function validate_database_username($username)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^([a-z0-9_\-\.\$]+)$/', $username))
            return lang('base_username_invalid');
    }

    /**
     * Validate if database is exisitng.
     *
     * @param string $database_name Database Name
     *
     * @return string error message if database name is not exists
     */

    public function validate_existing_database($database_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: SQL inject vulnerability, no _POST allowed in API calls.
        $admin_username = $_POST['database_admin_username'];
        $admin_password = $_POST['database_admin_password'];
        $command = "mysql -u $admin_username -p$admin_password -e \"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database_name'\"";
        $shell = new Shell();

        try {
            $retval = $shell->execute(
                self::COMMAND_MYSQL, $command, FALSE, $options
            );
        } catch (Engine_Exception $e) {
            return $e->get_message();
        }

        $output = $shell->get_output();
        $output_message = strtolower($output);

        if (strpos($output_message, 'error') !== FALSE)
            return lang('webapp_unable_connect_via_admin_user');
        else if(!$output)
            return lang('webapp_database_does_not_exist');
    }

    /**
     * Validation routine for file server state.
     *
     * @param boolean $state state
     *
     * @return error message if file server state is invalid
     */

    public function validate_file_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->validate_file_state($state);
    }

    /**
     * Validation routine for FTP state.
     *
     * @param boolean $state state
     *
     * @return error message if FTP state is invalid
     */

    public function validate_ftp_state($state)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->validate_ftp_state($state);
    }

    /**
     * Validation routine for group.
     *
     * @param string $group_name group name
     *
     * @return error message if group is invalid
     */

    public function validate_group($group_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->validate_group($group_name);
    }

    /**
     * Validate if database is new.
     *
     * @param string $database_name Database Name
     *
     * @return string error message if Database name is exists
     */

    public function validate_new_database($database_name)
    {
        clearos_profile(__METHOD__, __LINE__);

        // FIXME: SQL inject vulnerability, no _POST allowed in API calls.
        $admin_username = $_POST['database_admin_username'];
        $admin_password = $_POST['database_admin_password'];
        $command = "mysql -u $admin_username -p$admin_password -e \"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database_name'\"";
        $shell = new Shell();

        try {
            $retval = $shell->execute(self::COMMAND_MYSQL, $command, FALSE, $options);
        } catch (Engine_Exception $e) {
            return $e->get_message();
        }

        $output = $shell->get_output();
        $output_message = strtolower($output);

        if (strpos($output_message, 'error') !== FALSE)
            return lang('webapp_unable_connect_via_admin_user');
        else if($output)
            return lang('webapp_database_already_exists');
    }

    /**
     * Validation routine for site.
     *
     * @param string $site site
     *
     * @return string error message if site is invalid
     */

    public function validate_site($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->validate_site($site);
    }

    /**
     * Validation routine for SSL certificate.
     *
     * @param string $certificate certificate name
     *
     * @return string error message if certificate is invalid
     */

    public function validate_ssl_certificate($certificate)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->validate_ssl_certificate($certificate);
    }

    /**
     * Validation routine for version.
     *
     * @param string $version version
     *
     * @return error message if version is invalid
     */

    public function validate_version($version)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! in_array($version, $this->get_versions()))
            return lang('webapp_version_invalid');
    }
}

// vim: syntax=php ts=4
