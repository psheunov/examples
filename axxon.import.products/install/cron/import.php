<?php
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Axxon\Import\Products\Src\MdbConvert;
use Axxon\Import\Products\Src\UpdateProducts;


define('NO_KEEP_STATISTIC', true); //запрет сбора статистики
define('NOT_CHECK_PERMISSIONS', true); //отключение проверки прав на доступ к файлам и каталогам
define('BX_BUFFER_USED', true); // сбросит уровень буферизации CMain::EndBufferContent
define('LID', "s1");

ini_set('memory_limit', '2049M');

$_SERVER["DOCUMENT_ROOT"] = dirname(dirname(dirname(__DIR__)));

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

$APPLICATION->RestartBuffer();
while (ob_end_clean());

try {

    if (!(Loader::includeModule('main')
        && Loader::includeModule('highloadblock')
        && Loader::includeModule('iblock')
        && Loader::includeModule('axxon.import.products'))
    ) {
        throw new \Bitrix\Main\LoaderException('Error while load modules');
    }

    $statusList=[];
    $fieldEnum = (new CUserFieldEnum)->GetList([], ['USER_FIELD_NAME' => 'UF_STATUS']);

    while ($statusEnum = $fieldEnum->Fetch()) {
        $statusList[$statusEnum['VALUE']] = $statusEnum['ID'];
    }

    $mdbFiles = Bitrix\Highloadblock\HighloadBlockTable::compileEntity('ImportMdbFiles')->getDataClass();
    $rows = $mdbFiles::getList([
        'select' => ['ID','UF_FILE', 'UF_DATE', 'UF_STATUS'],
        'order' => ['UF_DATE' => 'DESC'],
        'filter' => ['UF_STATUS'=>$statusList['New']]
    ]);

    while ($row = $rows->fetch()) {
        try {
            $mdbFiles::update($row['ID'], ['UF_STATUS'=>$statusList['In progress']]);
            CEventLog::Add([
                "SEVERITY" => "SECURITY",
                "AUDIT_TYPE_ID" => "ITEGRATION_DB",
                "MODULE_ID" => "axxon.import.products",
                "DESCRIPTION" => 'Интеграция запущена ['.$row['ID'].']',
            ]);

            $fileName = CFile::GetPath($row['UF_FILE']);
            $mdb = new MdbConvert(\Bitrix\Main\Application::getDocumentRoot(). $fileName);
            $props = $mdb->getProps();
            $products = $mdb->getProducts();

            $catalogRu = new UpdateProducts($products,'RU','3');
            $catalogRu->updateProps($props);
            $catalogRu->updateCatalog();
            $catalogRu->updateSectionFilters($mdb->getSectionFilters());

            $catalogEn = new UpdateProducts($products,'EN','7');
            $catalogEn->updateProps($props);
            $catalogEn->updateCatalog();
            $catalogEn->updateSectionFilters($mdb->getSectionFilters());

            $catalogDe = new UpdateProducts($products,'DE','12');
            $catalogDe->updateProps($props);
            $catalogDe->updateCatalog();
            $catalogDe->updateSectionFilters($mdb->getSectionFilters());

            $catalogFr = new UpdateProducts($products,'FR','19');
            $catalogFr->updateProps($props);
            $catalogFr->updateCatalog();
            $catalogFr->updateSectionFilters($mdb->getSectionFilters());

            $catalogIt = new UpdateProducts($products,'IT','20');
            $catalogIt->updateProps($props);
            $catalogIt->updateCatalog();
            $catalogIt->updateSectionFilters($mdb->getSectionFilters());

            $mdbFiles::update($row['ID'], ['UF_STATUS'=>$statusList['Сompleted']]);
            CEventLog::Add([
                "SEVERITY" => "SECURITY",
                "AUDIT_TYPE_ID" => "ITEGRATION_DB",
                "MODULE_ID" => "axxon.import.products",
                "DESCRIPTION" => 'Интеграция звершина ['.$row['ID'].']',
            ]);


        } catch (Exception $e) {
            $mdbFiles::update($row['ID'], ['UF_STATUS'=>$statusList['Fail']]);
            CEventLog::Add([
                "SEVERITY" => "SECURITY",
                "AUDIT_TYPE_ID" => "ERROR_ITEGRATION",
                "MODULE_ID" => "axxon.import.products",
                "DESCRIPTION" => $e->getMessage(),
            ]);
        }

    }
} catch (Exception $e) {
    CEventLog::Add([
        "SEVERITY" => "SECURITY",
        "AUDIT_TYPE_ID" => "ERROR_ITEGRATION",
        "MODULE_ID" => "axxon.import.products",
        "DESCRIPTION" => $e->getMessage(),
    ]);
}
