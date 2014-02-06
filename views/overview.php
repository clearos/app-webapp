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
$this->lang->load('webapp');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($app_name . '/overview');
echo form_header(lang('base_overview'));
// echo form_banner("hjello");

// FIXME: translate

echo field_view('Home Page', "<a target='_blank' href='" . $home_urls[0] . "'>" . $home_urls[0] . "</a>");
if (!empty($home_urls[1]))
    echo field_view('', "<a target='_blank' href='" . $home_urls[1] . "'>" . $home_urls[1] . "</a>");
    
echo field_view('Administrator Login', "<a target='_blank' href='" . $admin_urls[0] . "'>" . $admin_urls[0] . "</a>");
if (!empty($admin_urls[1]))
    echo field_view('', "<a target='_blank' href='" . $admin_urls[1] . "'>" . $admin_urls[1] . "</a>");

echo field_view('Database Management', "<a target='_blank' href='$db_url'>$db_url</a>");

echo form_footer();
echo form_close();

