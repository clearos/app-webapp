<?php

/**
 * Webapp summary view.
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
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('webapp_site_name'),
);

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons  = array(anchor_custom('/app/' . $basename . '/site/add', lang('webapp_add_site'), 'high', array('target' => '_self')));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($sites as $value) {
    $item['title'] = $value['name'];
    $access_action = $base_path.$value['name'];
    $access_admin_action = $base_path.$value['name'].'/wp-admin';
    $delete_action = "javascript:";

    $item['anchors'] = button_set(
        array(
            anchor_custom($access_action, lang('webapp_access'), 'high', array('target' => '_blank')),
            anchor_delete($delete_action, 'low', array('class' => 'delete_project_anchor', 'data' => array('folder_name' => $value['name']))),
        )
    );

    $item['details'] = array($value['name']);

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('webapp_sites'),
    $buttons,
    $headers,
    $items
);

///////////////////////////////////////////////////////////////////////////////
// Make site delete confirm popup
///////////////////////////////////////////////////////////////////////////////

$title = lang('joomla_confirm_delete_project');
$message = form_open('joomla/delete');
$message = $message. field_checkbox("delete_sure","1", lang('joomla_yes_delete_this_project'));
$message = $message. field_checkbox("delete_database","1", lang('joomla_yes_delete_assigned_database'));
$message = $message. field_input('root_username', 'root', lang('joomla_mysql_root_username'));
$message = $message. field_password('root_password', '', lang('joomla_mysql_root_password'));
$message = $message. field_input('folder_name', '', 'Folder Name', FALSE, array('id' => 'deleting_folder_name'));
$message = $message. form_close();
$confirm = '#';
$trigger = '';
$form_id = 'delete_form';
$modal_id = 'delete_modal';

echo modal_confirm($title, $message, 'javascript:', $trigger, $form_id, $modal_id);

