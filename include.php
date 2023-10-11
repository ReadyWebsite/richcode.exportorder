<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Loader;

define('RC_EXPORT_ORDER_MODULE_ID', 'richcode.exportorder');

Loader::registerAutoLoadClasses(RC_EXPORT_ORDER_MODULE_ID, [
    'Richcode\Export\EventHandlers' => 'lib/EventHandlers.php',
    'Richcode\Export\B24'           => 'lib/B24.php',
    'Richcode\Export\Options\Config'=> 'lib/Options/Config.php',
    'Richcode\Export\Options\Render'=> 'lib/Options/Render.php',
]);