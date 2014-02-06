<?php

/**
 * Webapp settings view.
 *
 * @category   apps
 * @category   apps
 * @package    webapp
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2014 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/webapp/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('groups');
$this->lang->load('web_server');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array( 
        form_submit_update('submit'),
        anchor_cancel('/app/' . $app_name . '/settings')
    );
} else {
    $read_only = TRUE;
    $buttons = array( 
        anchor_edit('/app/' . $app_name . '/settings/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($app_name . '/settings/edit');
echo form_header(lang('base_settings'));

echo fieldset_header(lang('webapp_hostname'));
echo field_toggle_enable_disable('hostname_access', $hostname_access, lang('webapp_hostname_access'), $read_only);
echo field_input('hostname', $info['WebServerName'], lang('network_hostname'), $read_only);
echo field_input('aliases', $info['WebServerAlias'], lang('webapp_aliases'), $read_only);
echo fieldset_footer();

echo fieldset_header(lang('webapp_directory'));
echo field_toggle_enable_disable('directory_access', $directory_access, lang('webapp_directory_access'), $read_only);
echo field_input('directory', $directory, lang('webapp_directory'), $read_only);
echo fieldset_footer();

echo field_button_set($buttons);

echo form_footer();
echo form_close();
