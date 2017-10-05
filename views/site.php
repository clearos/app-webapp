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

$this->lang->load('base');
$this->lang->load('web_server');
$this->lang->load('certificate_manager');

///////////////////////////////////////////////////////////////////////////////
// Form handling
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = TRUE;
    $form_path = '/' . $webapp . '/site/edit/' . $site;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/' . $webapp . '/site'),
    );
} else {
    $read_only = FALSE;
    $form_path = '/' . $webapp . '/site/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/' . $webapp . '/site')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form_path);
echo form_header(lang('webapp_site'));

// General information 
//--------------------

echo fieldset_header(lang('web_server_web_site'));
echo field_input('site', $site, lang('web_server_web_site_hostname'), $read_only);
echo field_input('aliases', $aliases, lang('web_server_aliases'));
echo field_dropdown('ssl_certificate', $ssl_certificate_options, $ssl_certificate, lang('certificate_manager_digital_certificate'));
echo fieldset_footer();

// Setup information 
//------------------

if (!$read_only) {
    echo fieldset_header(lang('base_settings'));
    echo field_dropdown('webapp_version', $versions, $version, lang('webapp_version'), $read_only);
    echo field_dropdown('use_existing_database', array('Yes' => lang('base_yes'), 'No' => lang('base_no')), 'No', lang('webapp_use_existing_database'));
    echo field_input('database_name', '', lang('webapp_new_database_name'));
    echo field_input('database_username', 'testuser', lang('webapp_database_username'));
    echo field_password('database_password', '', lang('webapp_database_password'));
    echo field_input('database_admin_username', 'root', lang('webapp_database_admin_username'));
    echo field_password('database_admin_password', '', lang('webapp_database_admin_password'));
    echo fieldset_footer();
}

// Upload information 
//-------------------

echo fieldset_header(lang('web_server_upload_access'));
echo field_dropdown('group', $groups, $group, lang('base_group'));

if ($ftp_available)
    echo field_toggle_enable_disable('ftp', $ftp_enabled, lang('web_server_ftp_upload'));

if ($file_available)
    echo field_toggle_enable_disable('file', $file_enabled, lang('web_server_file_server_upload'));

echo fieldset_footer();

// Footer
//-------

echo field_button_set($buttons);

echo form_footer();
echo form_close();
