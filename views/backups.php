<?php

/**
 * Webapp backups View.
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

$this->lang->load('base');
$this->lang->load('webapp');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('webapp_backup'),
);

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons  = array(
    anchor_custom('/app/' . $webapp, lang('base_return_to_summary'))
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($backups as $value) {
    $item['title'] = $value['name'];
    $download_action = '/app/' . $webapp . '/backup/download/' . $value['name'];
    $delete_action = '/app/' . $webapp . '/backup/delete/' . $value['name'];
    $item['anchors'] = button_set(
        array(
            anchor_custom($download_action, lang('base_download'), 'high'),
            anchor_delete($delete_action, 'low'),
        )
    );

    $item['details'] = array(
        $value['name']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('webapp_backups'),
    $buttons,
    $headers,
    $items
);
