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
use \clearos\apps\base\File as File;
use \clearos\apps\base\Folder as Folder;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\webapp\Webapp_Engine as Webapp_Engine;
use \clearos\apps\web_server\Httpd as Httpd;

clearos_load_library('base/Engine');
clearos_load_library('base/File');
clearos_load_library('base/Folder');
clearos_load_library('base/Shell');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('webapp/Webapp_Engine');
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
    const COMMAND_MYSQLDUMP = '/usr/bin/mysqldump';
    const COMMAND_ZIP = '/usr/bin/zip';

    ///////////////////////////////////////////////////////////////////////////////
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $site = NULL;
    protected $webapp = NULL;
    protected $config = array();
    protected $backup_path = '';

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Webapp site constructor.
     *
     * @param string $webapp      webapp basename
     * @param string $site        site name
     * @param string $backup_path backup path name
     */

    public function __construct($webapp, $site, $backup_path)
    {
        clearos_profile(__METHOD__, __LINE__);

        $this->webapp = $webapp;
        $this->site = $site;
        $this->backup_path = $backup_path;
    }

    /**
     * Creates backup of site folder.
     *
     * @return void
     */

    public function backup()
    {
        clearos_profile(__METHOD__, __LINE__);

        $site_root = $this->get_site_root();

        $zip_path = $this->backup_path . '/' . $this->site . '__' . date('Y-m-d-H-i-s') . '.zip';
        $params = "-r $zip_path $site_root";

        $folder = new Folder($site_root);

        if ($folder->exists()) {
            $shell = new Shell();
            $shell->execute(self::COMMAND_ZIP, $params, TRUE);
        }
    }

    /**
     * Creates database backup.
     *
     * @param string $username database username
     * @param string $password database password
     *
     * @return void
     * @throws Engine_Exception
     */
 
    public function backup_database($username, $password)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_database_username($username));
        Validation_Exception::is_valid($this->validate_database_password($password));

        $database_name = $this->get_database_name();

        $sql_file_path = $this->backup_path . '/' . $database_name . '__' . date('Y-m-d-H-i-s') . '.sql';

        $params = " -u'$username' -p'$password' '$database_name' > '$sql_file_path'";

        $shell = new Shell();
        $shell->execute(self::COMMAND_MYSQLDUMP, $params, FALSE, $options);
    }

    /**
     * Checks for connectivity issues with database.
     *
     * @param string $username database username
     * @param string $password database password
     * @param string $database database name
     *
     * @return string error message if unable to connect with database
     */

    public function check_database($username, $password, $database = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_database_username($username));
        Validation_Exception::is_valid($this->validate_database_password($password));

        if (empty($database)) {
            $database = $this->get_database_name();
            $is_new = FALSE;
        } else {
            $is_new = TRUE;
        }

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

    /**
     * Deletes site.
     *
     * @param boolean $do_backup set to TRUE for creating backup
     *
     * @return void
     */

    public function delete($do_backup)
    {
        clearos_profile(__METHOD__, __LINE__);

        // Run backup if requested
        //------------------------

        if ($do_backup)
            $this->backup();

        // Delete web site via Httpd API
        // -----------------------------

        $httpd = new Httpd();
        $httpd->delete_site($this->site, TRUE);
    }

    /**
     * Deletes database with the option of creating a backup.
     *
     * @param string  $username  database username
     * @param string  $password  database password
     * @param boolean $do_backup flag to indicate backup
     *
     * @return Exception is somethings goes wrong with MYSQL 
     */

    public function delete_database($username, $password, $do_backup)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_database_username($username));
        Validation_Exception::is_valid($this->validate_database_password($password));

        if ($do_backup)
            $this->backup_database($username, $password);

        $database_name = $this->get_database_name();

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
     * @return string document root
     * @throws Engine_Exception
     */

    public function get_document_root()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_document_root($this->site);
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
     * Returns site root.
     *
     * @return string site root
     * @throws Engine_Exception
     */

    public function get_site_root()
    {
        clearos_profile(__METHOD__, __LINE__);

        $httpd = new Httpd();

        return $httpd->get_site_root($this->site);
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
}

// vim: syntax=php ts=4