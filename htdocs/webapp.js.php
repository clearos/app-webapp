<?php

/**
 * Webapp Javascript Helper.
 *
 * @category   apps
 * @package    webapp
 * @subpackage javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2017 ClearFoundation
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
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');
clearos_load_language('webapp');

///////////////////////////////////////////////////////////////////////////////
// J A V A S C R I P T
///////////////////////////////////////////////////////////////////////////////

header('Content-Type: application/x-javascript');
?>

//////////////////////////////////////////////////////////////////////////////
// For add/edit site page
///////////////////////////////////////////////////////////////////////////////

$(document).ready(function() {
    // Translations
    //-------------

    lang_existing_database_name = '<?php echo lang("webapp_existing_database_name"); ?>';
    lang_new_database_name = '<?php echo lang("webapp_new_database_name"); ?>';

    // Main
    //-----

    $("#use_existing_database").change(function(){
        selectDatabase();
    })
})

function selectDatabase()
{
    var option = $("#use_existing_database").val();

    if(option == "Yes")
        $("#database_name_label").text(lang_existing_database_name);
    else
        $("#database_name_label").text(lang_new_database_name);
}

// vim: ts=4 syntax=javascript
