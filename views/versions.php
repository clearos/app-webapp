<?php

/**
 * Webapp versions view.
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

$headers = array(
    lang('webapp_version'),
);

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$buttons  = array();

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

$items = array();

foreach ($versions as $value) {
    if ($value['clearos_path']) {
        $button = anchor_delete('/app/' . $webapp . '/version/delete/' . $value['file_name']);
    } else {
        $button = anchor_custom('/app/' . $webapp . '/version/download/' . $value['file_name'], lang('base_download'));
    }

    $item['anchors'] = button_set(array($button));
    $item['details'] = array(
        $webapp_description . ' ' . $value['version'],
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo summary_table(
    lang('webapp_versions'),
    $buttons,
    $headers,
    $items
);