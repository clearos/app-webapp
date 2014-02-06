<?php

/**
 * Webapp site view.
 *
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
        anchor_cancel('/app/' . $app_name . '/advanced')
    );
} else {
    $read_only = TRUE;
    $buttons = array( 
        anchor_edit('/app/' . $app_name . '/advanced/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($app_name . '/advanced/edit');
// TODO: translation requires app-base > 1.5.35 echo form_header(lang('base_advanced_settings'));
echo form_header('Advanced Settings');

echo field_dropdown('web_access', $accessibility_options, $info['WebAccess'], lang('flexshare_web_accessibility'), $read_only);
echo field_toggle_enable_disable('require_authentication', $info['WebReqAuth'], lang('flexshare_web_require_authentication'), $read_only);
echo field_toggle_enable_disable('show_index', $info['WebShowIndex'], lang('flexshare_web_show_index'), $read_only);
echo field_toggle_enable_disable('follow_symlinks', $info['WebFollowSymLinks'], lang('flexshare_web_follow_symlinks'), $read_only);
echo field_toggle_enable_disable('ssi', $info['WebAllowSSI'], lang('flexshare_web_allow_ssi'), $read_only);
echo field_toggle_enable_disable('htaccess', $info['WebHtaccessOverride'], lang('flexshare_web_allow_htaccess'), $read_only);
echo field_toggle_enable_disable('php', $info['WebPhp'], lang('flexshare_web_enable_php'), $read_only);
echo field_toggle_enable_disable('cgi', $info['WebCgi'], lang('flexshare_web_enable_cgi'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
