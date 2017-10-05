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

if ($dep_issues)
    $buttons = array();
else
    $buttons = array(anchor_custom('/app/' . $webapp . '/site/add', lang('webapp_add_site'), 'high', array('target' => '_self')));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($sites as $value) {
    $item['title'] = $value['name'];
    $access_action = $base_path.$value['name'];
    $access_admin_action = $base_path.$value['name'].'/wp-admin';
    $delete_action = 'javascript:';

    $item['anchors'] = button_set(
        array(
            anchor_custom($access_action, lang('webapp_access'), 'high', array('target' => '_blank')),
            anchor_edit('/app/' . $webapp . '/site/edit/' . $value['name']),
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

// FIXME: continue conversion
$title = lang('webapp_confirm_delete');
$message = form_open($webapp . '/site');
$message = $message. field_checkbox('delete_sure', '1', lang('webapp_delete_site'));
$message = $message. field_checkbox('delete_database', '1', lang('webapp_delete_database'));
$message = $message. field_input('admin_username', 'root', lang('webapp_database_admin_username'));
$message = $message. field_password('admin_password', '', lang('webapp_database_admin_password'));
$message = $message. field_input('site', '', lang('webapp_site'), FALSE, array('id' => 'deleting_site'));
$message = $message. form_close();
$confirm = '#';
$trigger = '';
$form_id = 'delete_form';
$modal_id = 'delete_modal';

echo modal_confirm($title, $message, 'javascript:', $trigger, $form_id, $modal_id);

