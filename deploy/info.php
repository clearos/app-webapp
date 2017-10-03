<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'webapp';
$app['version'] = '2.4.0';
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
$app['category'] = lang('base_category_server');
$app['subcategory'] = lang('base_subcategory_web');
$app['menu_enabled'] = FALSE;

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['core_requires'] = array(
    'app-base-core >= 1:1.5.24',
    'app-certificate-manager-core',
    'app-flexshare-core >= 1:1.5.30',
    'app-groups-core',
    'app-network-core',
    'app-web-server-core',
    'app-mariadb-core',
    'openssl',
);

$app['requires'] = array(
    'app-accounts',
    'app-network',
    'app-web-server',
    'app-mariadb',
);
