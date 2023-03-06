<?php

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */
class axxon_import_products extends CModule
{
    public $MODULE_ID = 'axxon.import.products';

    public $MODULE_VERSION = '0.0.1';
    public $MODULE_VERSION_DATE = '2019-12-13 00:00:00';

    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public $PARTNER_NAME;
    public $PARTNER_URI;

    protected $moduleRoot;

    /** @var CMain $application */
    protected $application;

    public function __construct()
    {
        Loc::loadMessages(__FILE__);

        $this->moduleRoot = dirname(__DIR__);

        global $APPLICATION;
        $this->application = $APPLICATION;

        $this->MODULE_NAME        = Loc::getMessage('AXXON_IMPORT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('AXXON_IMPORT_MODULE_DESC');
        $this->PARTNER_NAME       = Loc::getMessage('AXXON_IMPORT_PARTNER_NAME');
        $this->PARTNER_URI        = Loc::getMessage('AXXON_IMPORT_PARTNER_URI');
    }

    /**
     * @return bool
     */
    public function doInstall()
    {
        $this->InstallFiles();
        $this->InstallEvents();
        $this->InstallTasks();

        if (!$this->application->GetException()) {
            ModuleManager::registerModule($this->MODULE_ID);
            return true;
        }

        return false;
    }

    /**
     * @param array $params
     */
    public function InstallFiles(array $params = [])
    {
        CopyDirFiles(
            $this->moduleRoot . '/install/admin',
            Application::getDocumentRoot() . '/bitrix/admin',
            true
        );
        CopyDirFiles(
            $this->moduleRoot . '/install/cron',
            Application::getDocumentRoot() . '/local/php_interface/cron',
            true
        );
    }

    /**
     * @return bool
     */
    public function doUninstall()
    {
        $this->UnInstallFiles();
        $this->UnInstallEvents();
        $this->UnInstallTasks();

        if (!$this->application->GetException()) {
            ModuleManager::unRegisterModule($this->MODULE_ID);
            return true;
        }

        return false;
    }

    /**
     * @param array $params
     */
    public function UnInstallFiles(array $params = [])
    {
        DeleteDirFiles(
            $this->moduleRoot . '/install/admin',
            Application::getDocumentRoot() . '/bitrix/admin'
        );
        DeleteDirFiles(
            $this->moduleRoot . '/install/cron',
            Application::getDocumentRoot() . '/local/php_interface/cron'
        );
    }
}
