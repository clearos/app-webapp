<?php

/**
 * Webapp settings view.
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
$this->lang->load('webapp');

///////////////////////////////////////////////////////////////////////////////
// Warnings
///////////////////////////////////////////////////////////////////////////////

if (count($versions) === 0) {
    echo infobox_highlight(lang('base_warning'), lang('webapp_install_warning'));
    return;
}

if (!$is_web_server_running) {
    echo infobox_warning(
        lang('base_warning'), 
        lang('webapp_web_server_not_running') . '<p align="center">' . anchor_custom('/app/web_server', 'Configure Web Server') . '</p>'
    );
    return;
}

///////////////////////////////////////////////////////////////////////////////
// Infobox
///////////////////////////////////////////////////////////////////////////////

echo infobox_highlight(
    lang('base_welcome'),
    lang('webapp_initialize_help') . "
    <ul>
        <li>" . lang('webapp_hostname') . " - <b>http://$hostname</b></li>
        <li>" . lang('webapp_directory') . " - <b>http://$directory_url</b></li>
    </ul>"
);

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($app_name . '/initialize');
echo form_header(lang('base_initialize'));

echo fieldset_header(lang('base_settings'));
echo field_simple_dropdown('version', $versions, $version, lang('webapp_version'), $read_only);
echo fieldset_footer();

echo fieldset_header(lang('webapp_hostname_access'));
echo field_toggle_enable_disable('hostname_access', $hostname_access, lang('base_state'));
echo field_input('hostname', $hostname, lang('webapp_hostname'));
echo fieldset_footer();

echo fieldset_header(lang('webapp_directory_access'));
echo field_toggle_enable_disable('directory_access', $directory_access, lang('base_state'));
echo field_input('directory', $directory, lang('webapp_directory'));
echo fieldset_footer();

echo field_button_set(
    array( 
        form_submit_custom('submit', lang('base_initialize'))
    )
);

echo form_footer();
echo form_close();
