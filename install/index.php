<? defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;


if (class_exists('richcode_exportorder')) {
    return;
}

class richcode_exportorder
    extends CModule
{

    var $MODULE_ID = 'richcode.exportorder';

    public function __construct()
    {
        $arModuleVersion = [];

        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage("RC_INST_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("RC_INST_MODULE_DESC");
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = 'Rich code';
        $this->PARTNER_URI = 'https://richcode.ru/';
    }

    protected function getModuleDir()
    {
        preg_match('/.*modules/', __FILE__, $matches);

        if ($matches) {
            $moduleDir = "{$matches[0]}/{$this->MODULE_ID}";
        }
        else {
            $moduleDir = "{$_SERVER["DOCUMENT_ROOT"]}/bitrix/modules/{$this->MODULE_ID}";
        }

        return $moduleDir;
    }

    public function installFiles()
    {
        CopyDirFiles($this->getModuleDir() . "/install/admin", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin", true);

        return true;
    }

    public function uninstallFiles()
    {
        DeleteDirFiles($this->getModuleDir() . "/install/admin/", $_SERVER["DOCUMENT_ROOT"] . "/bitrix/admin/");

        return true;
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installFiles();
        $this->registerDependencies();
    }

    /**
     * @throws \Bitrix\Main\LoaderException
     */
    public function DoUninstall()
    {
        $this->uninstallFiles();
        $this->unregisterDependencies();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }


    public function registerDependencies()
    {
        $eManager = EventManager::getInstance();
        $eManager->registerEventHandler('sale', 'OnSaleOrderSaved', $this->MODULE_ID, 'Richcode\Export\EventHandlers', 'OnSaleOrderSaved', 10);
    }

    public function unregisterDependencies()
    {
        $eManager = EventManager::getInstance();
        $eManager->unRegisterEventHandler('sale', 'OnSaleOrderSaved', $this->MODULE_ID, 'Richcode\Export\EventHandlers', 'OnSaleOrderSaved');
    }
}