<?php

/**
 * Webapp upload view.
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

$this->lang->load('webapp');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = FALSE;
    $buttons = array( 
        form_submit_update('submit'),
        anchor_cancel('/app/' . $app_name . '/upload')
    );
} else {
    $read_only = TRUE;
    $buttons = array( 
        anchor_edit('/app/' . $app_name . '/upload/edit')
    );
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($app_name . '/upload/edit');
echo form_header(lang('webapp_upload_access'));

// Don't show group if it has not yet been defined (typically set to "nobody")
if (($form_type == 'edit') || array_key_exists($group, $groups))
    echo field_dropdown('group', $groups, $group, lang('webapp_group'), $read_only);
else
    echo field_view(lang('webapp_group'), '-');

if ($ftp_available)
    echo field_toggle_enable_disable('ftp', $ftp_access, lang('webapp_ftp_access'), $read_only);

if ($file_available)
    echo field_toggle_enable_disable('file', $file_access, lang('webapp_file_server_access'), $read_only);

echo field_button_set($buttons);

echo form_footer();
echo form_close();
