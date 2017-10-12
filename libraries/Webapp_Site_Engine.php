<?php

/**
 * Webapp engine class.
 *
 * @category   apps
 * @package    webapp
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013-2017 ClearFoundation
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

clearos_load_language('base');
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
use \clearos\apps\webapp\Webapp_Backup_Engine as Webapp_Backup_Engine;

clearos_load_library('base/Engine');
clearos_load_library('base/Shell');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('web_server/Httpd');
clearos_load_library('webapp/Webapp_Backup_Engine');

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
 * @copyright  2013-2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

class Webapp_Site_Engine extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const COMMAND_MYSQL = '/usr/bin/mysql';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $webapp = NULL;
    protected $backup_path = '';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp site constructor.
     *
     * @param string $webapp webapp basename
     */

    public function __construct($webapp)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Pull in Webapp configuruation.
        include clearos_app_base($webapp) . '/deploy/config.php';

        $this->webapp = $webapp;
        $this->backup_path = $config['backup_path'];
    }

    /**
     * Checks for connectivity issues with existing database.
     *
     * @param string $username database username
     * @param string $password database password
     * @param string $site     site name
     *
     * @return string error message if unable to connect with database
     */

    public function check_existing_database($username, $password, $site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $database = $this->get_database_name($site);

        return $this->_check_database($username, $password, $database, FALSE);
    }

    /**
     * Checks for connectivity issues with new database.
     *
     * @param string $username database username
     * @param string $password database password
     * @param string $database database name
     *
     * @return string error message if unable to connect with database
     */

    public function check_new_database($username, $password, $database)
    {
        clearos_profile(__METHOD__, __LINE__);

        return $this->_check_database($username, $password, $database, TRUE);
    }

    /**
     * Deletes site.
     *
     * @param string  $site      site
     * @param boolean $do_backup set to TRUE for creating backup
     *
     * @return void
     */

    public function delete($site, $do_backup)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_site($site));

        // Run backup if requested
        //------------------------

        if ($do_backup) {
            $site_root = $this->get_site_root($site);

            $backup = new Webapp_Backup_Engine($this->webapp); 
            $backup->backup_site($site, $site_root);
        }

        // Delete web site via Httpd API
        // -----------------------------

        $httpd = new Httpd();
        $httpd->delete_site($site, TRUE);
    }

    /**
     * Deletes database with the option of creating a backup.
     *
     * @param string  $site      site
     * @param string  $username  database username
     * @param string  $password  database password
     * @param boolean $do_backup flag to indicate backup
     *
     * @return void
     */

    public function delete_database($site, $username, $password, $do_backup)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_site($site));
        Validation_Exception::is_valid($this->validate_database_username($username));
        Validation_Exception::is_valid($this->validate_database_password($password));

        $database_name = $this->get_database_name($site);

        if ($do_backup) {
            $backup = new Webapp_Backup_Engine($this->webapp); 
            $backup->backup_database($database_name, $username, $password);
        }

        $params = " -u'$username' -p'$password' -e \"DROP DATABASE \"$database_name\"\"";

        $shell = new Shell();
        $retval = $shell->execute(self::COMMAND_MYSQL, $params, FALSE);
    }

    /**
     * Returns document root.
     *
     * The document root typically lives under the "html" directory in
     * the site's root directory.
     *
     * @param string $site site
     *
     * @return string document root
     * @throws Engine_Exception
     */

    public function get_document_root($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validation
        //-----------

        Validation_Exception::is_valid($this->validate_site($site));

        // Get document root
        //------------------

        $httpd = new Httpd();

        return $httpd->get_document_root($site);
    }

    /**
     * Returns state of file access.
     *
     * @param string $site site
     *
     * @return boolean TRUE if FTP access is enabled
     * @throws Engine_Exception
     */

    public function get_file_state($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_file_state($site);
    }

    /**
     * Returns state of FTP access.
     *
     * @param string $site site
     *
     * @return boolean TRUE if FTP access is enabled
     * @throws Engine_Exception
     */

    public function get_ftp_state($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_ftp_state($site);
    }

    /**
     * Returns group.
     *
     * @param string $site site
     *
     * @return string group name
     * @throws Engine_Exception
     */

    public function get_group($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_group($site);
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
     * Returns list of sites.
     *
     * @return array $list of all sites for given webapp
     */

    public function get_sites()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();
        $webapps = $httpd->get_webapps();

        $list = array();

        foreach ($webapps as $key => $value) {
            $list[$key]['name'] = $key;
            // $list[$key]['database'] = $this->get_database_name($key);
            // FIXME
        }

        return $list;
    }

    /**
     * Returns site root.
     *
     * @param string $site site
     *
     * @return string site root
     * @throws Engine_Exception
     */

    public function get_site_root($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_site_root($site);
    }

    /**
     * Returns configured SSL certificate.
     *
     * @param string $site site
     *
     * @return string SSL certificate name
     * @throws Engine_Exception
     */

    public function get_ssl_certificate($site)
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_ssl_certificate($site);

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
     * @param string $site        site
     * @param string $aliases     aliases
     * @param string $group       group owner
     * @param string $ftp_state   FTP enabled state
     * @param string $file_state  file enabled state
     * @param string $certificate SSL certificate
     *
     * @return  void
     * @throws  Engine_Exception
     */

    public function update($site, $aliases, $group, $ftp_state, $file_state, $certificate)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_site($site));
        Validation_Exception::is_valid($this->validate_aliases($aliases));
        Validation_Exception::is_valid($this->validate_group($group));
        Validation_Exception::is_valid($this->validate_ftp_state($ftp_state));
        Validation_Exception::is_valid($this->validate_file_state($file_state));
        Validation_Exception::is_valid($this->validate_ssl_certificate($certificate));

        $httpd = new Httpd();

        $httpd->set_webapp(
            $site,
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
     * Validate database delete flag.
     *
     * @param boolean $flag database delete flag
     *
     * @return string error message if delete flag is invalid
     */

    public function validate_database_delete_flag($flag)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! clearos_is_valid_boolean($flag))
            return lang('base_state_invalid');
    }

    /**
     * Validate database name.
     *
     * @param string $name database name
     *
     * @return string error message if database name is invalid
     */

    public function validate_database_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! preg_match('/^[\w_\-]+$/', $name))
            return lang('webapp_database_name_invalid');
    }

    /**
     * Validate database password.
     *
     * @param string $password password
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

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E  M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Checks for connectivity issues with database.
     *
     * @param string  $username database username
     * @param string  $password database password
     * @param string  $database database name
     * @param boolean $is_new   flag to indicate new database
     *
     * @return string error message if unable to connect with database
     */

    protected function _check_database($username, $password, $database, $is_new)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_database_username($username));
        Validation_Exception::is_valid($this->validate_database_password($password));

        $params = "-u'$username' -p'$password' -e \"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'\"";
        $shell = new Shell();

        try {
            $shell->execute(self::COMMAND_MYSQL, $params, FALSE);
        } catch (Engine_Exception $e) {
            $output_message = preg_replace('/^error[^:]*:/i', '', $e->getMessage());

            return $output_message;
        }

        $output = $shell->get_output();

        if (strpos($output_message, 'error') !== FALSE)
            return $output_message;
        else if (!$is_new && !$output)
            return lang('webapp_database_does_not_exist');
        else if ($is_new && $output)
            return lang('webapp_database_already_exists');
    }
}

// vim: syntax=php ts=4
