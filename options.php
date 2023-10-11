<?
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use \Bitrix\Main\Loader;
use \Bitrix\Main\Localization\Loc;
use Richcode\Export\Options\Config;
use Richcode\Export\Options\Render;


Loader::includeModule('richcode.exportorder');

Loc::loadLanguageFile(__FILE__);


$arOptions = [];

$arOptions['tab0'] = [
    'USE_API'  => [
        'type'  => 'checkbox',
        'name'  => 'USE_API',
        'label' => Loc::getMessage('RC_OPTION_USE_API'),
        'hint'  => Loc::getMessage('RC_OPTION_USE_API_HINT'),
    ],
    'URL' => [
        'type'  => 'text',
        'name'  => 'URL',
        'label' => Loc::getMessage('RC_OPTION_URL'),
    ],
    'AUTH_ID' => [
        'type'  => 'text',
        'name'  => 'AUTH_ID',
        'label' => Loc::getMessage('RC_OPTION_AUTH_ID'),
    ],
    'USER_ID' => [
        'type'  =>  'text',
        'name'  =>  'USER_ID',
        'label' =>  Loc::getMessage('RC_OPTION_USER_ID'),
    ],
];



$aTabs = [
    [
        "DIV"   => "tab0",
        "TAB"   => Loc::getMessage('RC_OPTION_SETTINGS'),
        "TITLE" => Loc::getMessage('RC_OPTION_SETTINGS'),
    ],
];

$request = Bitrix\Main\Context::getCurrent()->getRequest();

Config::saveSettings();
$arData = Config::getSettings();

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();
?>

<form method="POST">
    <input type="hidden" name="lang" value="<?=LANG?>"/>
    <input type="hidden" name="SID" value="<?=htmlspecialchars($SID)?>"/>
    <?=bitrix_sessid_post()?>

    <? foreach ($aTabs as $tab) :
        $tabControl->BeginNextTab();
        echo Render::generateTab($arOptions[$tab['DIV']], $arData);
    endforeach; ?>

    <? $tabControl->buttons(); ?>

    <input type="submit" name="save" value="<?= Loc::getMessage('RC_OPTION_SAVE')?>" title="<?= Loc::getMessage('RC_OPTION_SAVE')?>" class="adm-btn-save"/>

    <? $tabControl->End(); ?>
</form>