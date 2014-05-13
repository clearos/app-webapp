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
// Web server warning
///////////////////////////////////////////////////////////////////////////////

if (!$is_web_server_running)
    echo infobox_warning(lang('base_warning'), lang('webapp_web_server_not_running'));

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($app_name . '/overview');
echo form_header(lang('base_overview'));

if ($is_web_server_running) {
    echo field_view(lang('webapp_home_page'), "<a target='_blank' href='" . $home_urls[0] . "'>" . $home_urls[0] . "</a>");
    if (!empty($home_urls[1]))
        echo field_view('', "<a target='_blank' href='" . $home_urls[1] . "'>" . $home_urls[1] . "</a>");
        
    echo field_view(lang('webapp_administrator_login'), "<a target='_blank' href='" . $admin_urls[0] . "'>" . $admin_urls[0] . "</a>");
    if (!empty($admin_urls[1]))
        echo field_view('', "<a target='_blank' href='" . $admin_urls[1] . "'>" . $admin_urls[1] . "</a>");
}

if (! empty($database_url))
    echo field_view(lang('webapp_database_management'), "<a target='_blank' href='$database_url'>" . lang('webapp_connect') . "</a>");

echo form_footer();
echo form_close();

