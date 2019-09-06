<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'SeetaAiBuildingCommunity\Models' => APP_PATH . '/common/models/',
    'SeetaAiBuildingCommunity'        => APP_PATH . '/common/library/',
    'SeetaAiBuildingCommunity\Modules\Backend\Controllers' => APP_PATH.'/modules/backend/controllers/',
    'SeetaAiBuildingCommunity\Common\Library' => APP_PATH.'/common/library/',
    'SeetaAiBuildingCommunity\Common\Manager' => APP_PATH.'/common/manager/',
    'SeetaAiBuildingCommunity\Common\Manager\Operation' => APP_PATH.'/common/manager/detectOperation',
    'SeetaAiBuildingCommunity\Models\Base' => APP_PATH.'/common/models/base',
    'SeetaAiBuildingCommunity\Models\Traits' => APP_PATH.'/common/models/traits',
]);

/**
 * Register module classes
 */
$loader->registerClasses([
    'SeetaAiBuildingCommunity\Modules\Backend\Module' => APP_PATH . '/modules/backend/Module.php',
    'SeetaAiBuildingCommunity\Modules\Cli\Module'      => APP_PATH . '/modules/cli/Module.php'
]);

$loader->register();
