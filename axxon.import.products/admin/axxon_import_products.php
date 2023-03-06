<?php

use Bitrix\Main\Application;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

try {
    Loader::includeModule('axxon.import.products');
    Loader::includeModule('highloadblock');

    /** @var HttpRequest $request */
    $request = Application::getInstance()->getContext()->getRequest();
    $importMdbFiles = Bitrix\Highloadblock\HighloadBlockTable::compileEntity('ImportMdbFiles')->getDataClass();
    $statusList=[];
    $rsEnum = CUserFieldEnum::GetList([], ['USER_FIELD_NAME' => 'UF_STATUS']);
    while($statusEnum = $rsEnum->Fetch()) {
        $statusList[$statusEnum["VALUE"]] =$statusEnum["ID"];
    }

    if (!check_bitrix_sessid()) {

    } elseif ($request->isAjaxRequest() && $request->isPost()) {


        $result = $importMdbFiles::add([
            'UF_DATE' => \Bitrix\Main\Type\Date::createFromTimestamp(time()),
            'UF_FILE' => CFile::MakeFileArray(
                Application::getDocumentRoot() . '/' . $request->getPost('mdbFile')
            ),
            'UF_STATUS' => $statusList['New']
        ]);

        if ($result->getId()) {

        } else {

        }
    }

    $tabs = [
        [
            'DIV' => 'import-file',
            'TAB' => Loc::getMessage('AXXON_IMPORT_TAB_NAME'),
            'ICON' => 'main_user_edit',
            'TITLE' => Loc::getMessage('AXXON_IMPORT_TAB_TITLE')
        ]
    ];

    $tabControl = new CAdminTabControl('editTab', $tabs);

    ?>
    <script type="text/javascript">
        /**
         *
         */
        function importFile()
        {

            if($('#mdb-file').val().trim()!=''){
                console.log($('#mdb-file').val());
                $.post(
                    'axxon_import_products.php',
                    $('#load-file-form').serialize(),
                    function () {
                        location.reload();
                    }
                )
            }

        }
    </script>
<?php
$userRight = $APPLICATION->GetGroupRight('axxon.import.products');
 if($userRight > 'R'):?>
    <form name="loadFileForm" id="load-file-form" action="<?= $APPLICATION->GetCurPage(); ?>?lang=<?= LANG; ?>" method="POST">
        <?php
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>

        <tr class="adm-detail-required-field">
            <td width="40%"><?php echo Loc::getMessage('AXXON_IMPORT_UPLOAD_FILE');?>:</td>
            <td width="60%">
                <input type="text"
                       id="mdb-file"
                       name="mdbFile"
                       required
                       size="30">
				<input type="button" value="<?php echo Loc::getMessage('AXXON_IMPORT_CHOOSE_FILE');?>" OnClick="selectFile()">
                <?php
                CAdminFileDialog::ShowScript([
                    'event'            => 'selectFile',
                    'select'           => 'F',
                    'operation'        => 'O',
                    'showUploadTab'    => true,
                    'showAddToMenuTab' => false,
                    'fileFilter'       => 'mdb',
                    'allowAllFiles'    => true,
                    'SaveConfig'       => true,
                    'arResultDest' => [
                        'FORM_NAME' => 'loadFileForm',
                        'FORM_ELEMENT_NAME' => 'mdbFile'
                    ],
                    'arPath' => [
                        'SITE' => SITE_ID,
                        'PATH' =>'/upload'
                    ]
                ]);
                ?>
            </td>
        </tr>

        <?php $tabControl->Buttons(); ?>
        <input type="button" id="import-button" value="<?= Loc::getMessage('AXXON_IMPORT_BUTTON');?>" class="adm-btn-save" OnClick="importFile();">
        <?php echo bitrix_sessid_post(); ?>
        <?php $tabControl->End(); ?>
    </form>
<?php endif;?>
    <br>

    <?php
    $sTableID = "my_list";
    $oSort = new CAdminSorting($sTableID, "timestamp_x", "desc");
    $lAdmin = new CAdminList($sTableID, $oSort);


    $lAdmin->AddHeaders([
        [
            'id'       => 'FILE',
            'content'  => Loc::getMessage('AXXON_IMPORT_FILE_NAME'),
            'default'  => true,
        ],
        [
            'id'       => 'DATA',
            'content'  => Loc::getMessage('AXXON_IMPORT_FILE_DATE'),
            'default'  => true,
        ],
        [
            'id'       => 'STATUS',
            'content'  => Loc::getMessage('AXXON_IMPORT_FILE_STATUS'),
            'default'  => true,
        ]
    ]);

    $rows = $importMdbFiles::getList([
        'select' => ['UF_FILE', 'UF_DATE', 'UF_STATUS'],
        'order' => ['UF_DATE' => 'DESC'],
    ]);

    while ($row = $rows->fetch()) {
        $fileStatus = CUserFieldEnum::GetList([], ['USER_FIELD_NAME' => 'UF_STATUS', 'ID' => $row['UF_STATUS']])->fetch()['VALUE'];
        $fileName = CFile::GetByID($row['UF_FILE'])->fetch()['ORIGINAL_NAME'].'_'.$row['UF_DATE'];
        $lAdmin->AddRow($row['ID'],[ 'FILE'=>$fileName,'DATA'=>$row['UF_DATE'],'STATUS'=>$fileStatus]);

    }

    $lAdmin->Display();




} catch (Exception $e) {
    CEventLog::Add([
        "SEVERITY" => "SECURITY",
        "AUDIT_TYPE_ID" => "ERROR_ITEGRATION",
        "MODULE_ID" => "axxon.import.products",
        "DESCRIPTION" => $e->getMessage(),
    ]);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
