<?php

/**
 * Webapp site view.
 *
 * @category   apps
 * @package    webapp
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('web_server');
$this->lang->load('certificate_manager');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);
echo form_header(lang('base_add'));

// General information 
//--------------------

echo fieldset_header(lang('web_server_web_site'));
echo field_input('site', $site, lang('web_server_web_site_hostname'), $site_read_only);
echo field_input('aliases', $aliases, lang('web_server_aliases'), $read_only);
echo fieldset_footer();

// Version and security
//---------------------

echo fieldset_header(lang('base_settings'));
echo field_dropdown('joomla_version', $versions, $default_version, lang('webapp_version'));
echo field_dropdown('ssl_certificate', $ssl_certificate_options, $ssl_certificate, lang('certificate_manager_digital_certificate'), $read_only);
echo fieldset_footer();

// Database information 
//--------------------

echo fieldset_header(lang('webapp_database'));
echo field_dropdown('use_exisiting_database', array('Yes' => lang('joomla_select_yes'), 'No' => lang('joomla_select_no')), 'No', lang('joomla_use_existing_database'));
echo field_input('database_name', '', lang('joomla_database_name'));
echo field_input('database_user_name', 'testuser', lang('joomla_database_username'));
echo field_password('database_user_password', '', lang('joomla_database_password'));
echo field_input('root_username', 'root', lang('joomla_mysql_root_username'));
echo field_password('root_password', '', lang('joomla_mysql_root_password'));
echo fieldset_footer();

// Upload information 
//-------------------

echo fieldset_header(lang('web_server_upload_access'));
echo field_dropdown('group', $groups, $group, lang('groups_group'), $read_only);

if ($ftp_available)
    echo field_toggle_enable_disable('ftp', $ftp_enabled, lang('web_server_ftp_upload'), $read_only);

if ($file_available)
    echo field_toggle_enable_disable('file', $file_enabled, lang('web_server_file_server_upload'), $read_only);

echo fieldset_footer();

// Footer
//-------

echo field_button_set(
    array(
    	anchor_cancel('/app/joomla'),
    	form_submit_add('submit', 'high')
    )
);

echo form_footer();
echo form_close();
