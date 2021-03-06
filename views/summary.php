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
    $item['anchors'] = button_set(
        array(
            anchor_custom('https://' . $value['name'], lang('webapp_access'), 'high', array('target' => '_blank')),
            anchor_edit('/app/' . $webapp . '/site/edit/' . $value['name']),
            anchor_delete('/app/' . $webapp . '/site/delete/' . $value['name'])
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
