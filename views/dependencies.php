<?php

/**
 * Webapp dependencies view.
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

$options['buttons']  = array(
    anchor_custom('/app/' . $webapp . '/backup/index', "Backups", 'high', array('target' => '_self')),
    anchor_custom('/app/mariadb', "MariaDB Server", 'high', array('target' => '_blank')),
    anchor_custom('/app/web_server', "Web Server", 'high', array('target' => '_blank')),
);

if ($readiness) {
    $lines = '<ul>';
    foreach ($readiness as $line)
        $lines .= '<li>' . $line . '</li>';
    $lines .= '</ul>';

    echo infobox_warning(lang('base_warning'), $lines);
}

echo infobox_highlight(
    lang('webapp_dependencies'),
    lang('webapp_dependencies_description'),
    $options
);
