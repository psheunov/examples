<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages($_SERVER['DOCUMENt_ROOT'].BX_ROOT.'/modules/main/options.php');
Loc::loadMessages(__FILE__);


$module_id = 'axxon.import.products';
\Bitrix\Main\Loader::includeModule($module_id);
$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();



$aTabs = [
    [
        'DIV'       => 'edit',
        'TAB'       => 'Доступ',
        'TITLE'     => 'Доступ',
    ]
];


if($request->isPost() && $request['Update'] && check_bitrix_sessid()){
    foreach ($aTabs as $aTab){
        foreach ($aTab['OPTIONS'] as $arOption)
            if(!is_array($arOption))
                continue;
        if($arOption['note'])
            continue;
        $optionName = $arOption[0];
        $optionValue = $request->getPost($optionName);
        Option::set($module_id,$optionName, is_array($optionValue) ? implode(',',$optionValue): $optionValue);
    }
}




$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>
<? $tabControl->Begin(); ?>
<form method='post'  action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?=htmlspecialcharsbx($request['mid']) ?>&lang=<? echo $request['lang']; ?>" name='brainkit_d7_settings'>
    <?
        foreach($aTabs as $aTab):
            if($aTab[ 'OPTIONS']):?>
            <?$tabControl->BeginNextTab();?>
            <?__AdmSettingsDrawList($module_id, $aTab['OPTIONS']);?>
            <?
            endif;
        endforeach;
            ?>
         <?
         $tabControl->BeginNextTab();
         require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");
         $tabControl->Buttons();
         ?>

    <input type="submit" name="Update" value="Сохранить">
    <input type="reset" name="reset" value="Сбросить">
    <?=bitrix_sessid_post();?>
</form>
<? $tabControl->End(); ?>