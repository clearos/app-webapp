<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'webapp';
$app['version'] = '1.0.0';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['description'] = lang('webapp_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('webapp_app_name');
$app['category'] = lang('base_category_system');
$app['subcategory'] = 'Developer'; // e.g. lang('base_subcategory_settings');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-base-core >= 1:1.5.24',
    'app-flexshare-core >= 1:1.5.30',
    'app-groups-core',
    'app-network-core',
    'app-web-server-core',
    'unzip',
    'tar',
);


$app['requires'] = array(
    'app-accounts',
    'app-network',
    'app-web-server',
);
