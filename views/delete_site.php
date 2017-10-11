<?php

/**
 * Webapp site delete view.
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

$this->lang->load('webapp');

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open('/' . $webapp . '/site/delete/' . $site);
echo form_header(lang('webapp_confirm_delete'));

echo field_checkbox('database_delete', $database_delete, lang('webapp_delete_database'));
echo field_input('database_delete_username', $database_admin_username, lang('webapp_database_admin_username'), FALSE, [ 'hide_field' => TRUE ]);
echo field_password('database_delete_password', $database_admin_password, lang('webapp_database_admin_password'), FALSE, [ 'hide_field' => TRUE ]);

echo field_button_set([
        form_submit_delete('submit', 'high'),
        anchor_cancel('/app/' . $webapp . '/site')
    ]
);

echo form_footer();
echo form_close();
