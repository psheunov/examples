<?php

namespace Axxon\Import\Products\Src;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock\SectionTable;
use CEventLog;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use CIBlockSection;
use CIBlockSectionPropertyLink;
use CModule;

/**
 * Class UpdateProducts
 */
class  UpdateProducts
{
    const FILE_PATH = 'https://download.grundig-security.com/';

    /**
     * Язык по умолчанию
     */
    const DEFAULT_LANG = 'EN';

    /** @var */
    private $products;
    /** @var */
    private $langCode;
    /** @var int */
    private $iBlockId;
    /** @var array */
    private $langMap = [
        'EN' => 'sprache0',
        'DE' => 'sprache1',
        'FR' => 'sprache2',
        'IT' => 'sprache3',
        'RU' => 'sprache7'
    ];
    /** @var array */
    private $langDescMap = [
        'EN' => 'DESCRIPTION_L_0',
        'DE' => 'DESCRIPTION_L_1',
        'FR' => 'DESCRIPTION_L_2',
        'IT' => 'DESCRIPTION_L_3',
        'RU' => 'DESCRIPTION_L_7'
    ];

    /**
     * UpdateProducts constructor.
     *
     * @param $products
     * @param $lang
     * @param $iBlockId
     */
    public function __construct($products, $lang, $iBlockId)
    {
        $this->products = $products;
        $this->langCode = $lang;
        $this->iBlockId = (int)$iBlockId;
    }

    /**
     * @param $props
     */
    public function updateProps($props)
    {

        if (CIBlock::GetArrayByID($this->iBlockId, "SECTION_PROPERTY") !== "Y") {
            $ib = new CIBlock;
            $ib->Update($this->iBlockId, ["SECTION_PROPERTY" => "Y"]);
        }

        $iBlockProps = $this->getIBlockProperties();

        $cIBlockProperty = new CIBlockProperty();
        foreach ($props as $key => $prop) {
            $newKey = preg_replace("/[^0-9a-z-_]/uim", '', $key);
            if (array_key_exists($newKey, $iBlockProps)) {
                $cIBlockProperty->Update($iBlockProps[$newKey], [
                    'NAME'             => trim($props[$key][$this->langMap[$this->langCode]]),
                    'ACTIVE'           => 'Y',
                    'SORT'             => (int)trim($props[$key]['sort']),
                    'CODE'             => $newKey,
                    'IBLOCK_ID'        => $this->iBlockId,
                    'SECTION_PROPERTY' => "Y",
                    'SMART_FILTER'     => 'N'
                ]);
                unset($props[$key]);
            } elseif (!empty($props[$key][$this->langMap[$this->langCode]])) {
                $cIBlockProperty->Add([
                    'NAME'          => trim($props[$key][$this->langMap[$this->langCode]]),
                    'ACTIVE'        => 'Y',
                    'SORT'          => (int)trim($props[$key]['sort']),
                    'CODE'          => $newKey,
                    'PROPERTY_TYPE' => 'S',
                    'IBLOCK_ID'     => $this->iBlockId
                ]);
            }
        }
    }

    /**
     *
     */
    public function updateProductSections()
    {
        $sectionsTree = $this->getSectionsTree();
        foreach ($this->products as $key => $product) {

            $level1 = $this->getSectionId($sectionsTree, $product['H0'][0]);
            if (!$sectionsTree[$level1]) {
                $sectionsTree = $this->getSectionsTree();
            }

            $level2 = $this->getSectionId($sectionsTree[$level1]['CHILD'], $product['H1'][0], $level1);
            if (!$sectionsTree[$level1]['CHILD'][$level2]) {
                $sectionsTree = $this->getSectionsTree();
            }

            $level3 = $this->getSectionId($sectionsTree[$level1]['CHILD'][$level2]['CHILD'], $product['H2'][0],
                $level2);
            if (!$sectionsTree[$level1]['CHILD'][$level2]['CHILD'][$level3]) {
                $sectionsTree = $this->getSectionsTree();
            }

            $this->products[$key]['L_1'] = $level1;
            $this->products[$key]['L_2'] = $level2;
            $this->products[$key]['L_3'] = $level3;
        }
    }

    /**
     *
     */
    public function updateCustomProps()
    {
        foreach ($this->products as $key => $product) {
            $dataSheet = ($this->langCode == self::DEFAULT_LANG)
                ? $this->getAllLangFile($product['docs'], 'Data Sheet')
                : $this->getFileUrl($product['docs'], 'Data Sheet');
            $props = [
                'ARTNUMBER'         => trim($product['KZ_BEZ']),
                'CERTIFICATES'      => $this->getFileUrl($product['docs'], 'CE Document'),
                'SPECIFICATION'     => $dataSheet,
                'INSTALLER_MANUAL'  => $this->getFileUrl($product['docs'], 'Installer Manual'),
                'INSTRUCTION'       => $this->getFileUrl($product['docs'], 'User Guide'),
                'Product_Guide'     => $this->getFileUrl($product['docs'], 'Product_Guide'),
                'Firmware_f'        => $this->getFileUrl($product['docs'], 'Grundig Firmware', '/upload/firmware/'),
                'Software1'         => $this->getFileUrl($product['docs'], 'Software 1'),
                'Software2'         => $this->getFileUrl($product['docs'], 'Software 2'),
                'BRIEF_INSTRUCTION' => $this->getFileUrl($product['docs'], 'Quick Start Guide')

            ];

            foreach ($product['icons'] as $item) {
                $props['OSOBENOSTI'][] = [
                    'VALUE'       => $item['id'],
                    'DESCRIPTION' => $item['sortierung']
                ];
            }

            //Превью текст товара
            usort($product['featureids'], function ($item1, $item2) {
                return $item1['sortierung'] <=> $item2['sortierung'];
            });
            $i = 0;
            $previewText = '<ul class="product-specification p-0 text-left">';
            foreach ($product['featureids'] as $text) {
                if ($i >= 5) {
                    break;
                }
                $previewText .= '<li class="product-specification__item">' . $text[$this->langMap[$this->langCode]] . '</li>';
                $i++;
            }
            $previewText .= '</ul>';
            $this->products[$key]['previewText'] = $previewText;

            $relatedProd = [];
            foreach ($product['accid'] as $related) {
                $relatedProd[] = $related['KZ_BEZ'];
            }
            $this->products[$key]['relatedProd'] = $relatedProd;

            foreach ($product['props'] as $id => $prop) {
                $props[$id] = [
                    'VALUE'       => $prop[$this->langMap[$this->langCode]],
                    'DESCRIPTION' => $prop['sort']
                ];
            }
            $this->products[$key]['PROPS'] = $props;
            $this->products[$key]['PICTURE'] = $this->getFileUrl($product['docs'], 'Images Main Small');
            $this->products[$key]['DETAIL_PICTURE'] = $this->getFileUrl($product['docs'], 'Images Main');

        }
    }

    /**
     * @return bool
     */
    public function updateCatalog()
    {
        $this->updateProductSections();
        $this->updateCustomProps();

        $currentProductList = $this->getCurrentProductList();
        $blockElement = new CIBlockElement();
        foreach ($this->products as $product) {
            if ($product['name'][0][$this->langDescMap[$this->langCode]]) {
                $name = $product['name'][0][$this->langDescMap[$this->langCode]];
            } else {
                $name = $product['PROPS']['ARTNUMBER'];
            }
            $product['PROPS']['PICTURE'] = $product['PICTURE'];
            $product['PROPS']['DETAIL_PICTURE'] = $product['DETAIL_PICTURE'];

            if (!array_key_exists(trim($product['KZ_BEZ']), $currentProductList) && (int)$product['ART_STATUS'] > 0) {
                $id = $blockElement->Add([
                    'DATE_CREATE'       => date('d.m.Y H:i:s', strtotime($product['ANL_DAT'])), //Передаем дата создания
                    'IBLOCK_ID'         => $this->iBlockId, //ID информационного блока он 24-ый
                    'PROPERTY_VALUES'   => $product['PROPS'], // Передаем массив значении для свойств
                    'NAME'              => strip_tags($name),
                    'PREVIEW_TEXT_TYPE' => 'html',
                    'PREVIEW_TEXT'      => $product['previewText'],
                    'SORT'              => $product['SORT'],
                    'DETAIL_TEXT_TYPE'  => 'html',
                    'CODE'              => mb_strtolower(trim($product['KZ_BEZ'])),
                    'IBLOCK_SECTION'    => [
                        $product['L_1'],
                        $product['L_2'],
                        $product['L_3']
                    ]
                ]);
                if ($id) {
                    CEventLog::Add([
                        "SEVERITY"      => "SECURITY",
                        "AUDIT_TYPE_ID" => "ADD_PRODUCT",
                        "MODULE_ID"     => "axxon.import.products",
                        "ITEM_ID"       => '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $this->iBlockId . '&type=products&ID=' . $id,
                        "DESCRIPTION"   => 'Добавлен новый товар',
                    ]);
                }
            } elseif((int)$product['ART_STATUS'] > 0) {
                $id = $currentProductList[trim($product['KZ_BEZ'])];
                unset($currentProductList[trim($product['KZ_BEZ'])]);
                $blockElement->Update($id, [
                    'ACTIVE'       => 'Y',
                    'DATE_CREATE'  => date('d.m.Y H:i:s', strtotime($product['ANL_DAT'])),
                    'CODE'         => mb_strtolower(trim($product['KZ_BEZ'])),
                    'PREVIEW_TEXT' => $product['previewText'],
                    'SORT'         => $product['SORT'],
                    'NAME'         => strip_tags($name),
                ]);
                $blockElement->SetPropertyValues($id, $this->iBlockId, $product['PROPS']);

            }
        }
        foreach ($currentProductList as $code => $siteProd) {
            $arLoadProductArray = ['ACTIVE' => 'N'];
            $blockElement->Update($siteProd, $arLoadProductArray);
            CEventLog::Add([
                "SEVERITY"      => "SECURITY",
                "AUDIT_TYPE_ID" => "ADD_PRODUCT",
                "MODULE_ID"     => "axxon.import.products",
                "ITEM_ID"       => '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $this->iBlockId . '&type=products&ID=' . $siteProd,
                "DESCRIPTION"   => "Товар диактивирован",
            ]);
        }

        $currentProductList = $this->getCurrentProductList();
        foreach ($this->products as $product) {
            if ($product['accid']) {
                $related = [];
                foreach ($product['accid'] as $accid) {
                    $related[] = $currentProductList[$accid['KZ_BEZ']];
                }
                CIBlockElement::SetPropertyValuesEx(
                    $currentProductList[$product['KZ_BEZ']],
                    false,
                    ['RELATED_PRODUCTS' => $related]
                );
            }
        }

        return true;
    }

    /**
     * @param $sections
     * @param $flatSection
     * @param string $parentSection
     * @return bool|int
     */
    private function getSectionId($sections, $flatSection, $parentSection = '')
    {
        $blockSection = new CIBlockSection();
        foreach ($sections as $section) {
            $code = mb_strtolower(preg_replace('/[^\da-z-_]+/miu', '-', trim($flatSection[$this->langMap[self::DEFAULT_LANG]])));
            if ((int)$section['XML_ID'] == (int)$flatSection['ID_PC'] || $section['CODE'] == $code) {
                $blockSection->Update(
                    $section['ID'],
                    [
                        'CODE'   => $code,
                        'NAME'   => trim($flatSection[$this->langMap[$this->langCode]]),
                        'SORT'   => $flatSection['Sortierung'],
                        'XML_ID' => $flatSection['ID_PC']
                    ]
                );
                return (int)$section['ID'];
            }
        }

        return $this->addSection($parentSection, $flatSection);
    }

    /**
     * @return mixed
     */
    private function getSectionsTree()
    {
        $sectionList = CIBlockSection::GetList(
            [
                'DEPTH_LEVEL' => 'ASC',
                'SORT'        => 'ASC'
            ],
            [
                'IBLOCK_ID' => $this->iBlockId,
            ],
            false,
            [
                'ID',
                'DEPTH_LEVEL',
                'IBLOCK_SECTION_ID',
                'CODE',
                'XML_ID'
            ]
        );


        $sectionTree = [];
        while ($section = $sectionList->GetNext()) {
            $sectionTree[(int)$section['IBLOCK_SECTION_ID']]['CHILD'][$section['ID']] = $section;
            $sectionTree[$section['ID']] = &$sectionTree[(int)$section['IBLOCK_SECTION_ID']]['CHILD'][$section['ID']];
        }

        return $sectionTree[0]['CHILD'];
    }

    /**
     * @param $parentSection
     * @param $newSection
     * @return bool|int
     */
    private function addSection($parentSection, $newSection)
    {
        if ($code = preg_replace('/[^\da-z-_]+/miu', '-', trim($newSection[$this->langMap[self::DEFAULT_LANG]]))) {
            return (new CIBlockSection())->Add([
                'ACTIVE'            => 'Y',
                'IBLOCK_SECTION_ID' => $parentSection,
                'IBLOCK_ID'         => $this->iBlockId,
                'NAME'              => trim($newSection[$this->langMap[$this->langCode]]),
                'SORT'              => $newSection['Sortierung'],
                'XML_ID'            => $newSection['ID_PC'],
                'CODE'              => mb_strtolower($code),
            ]);
        }

        return false;
    }

    /**
     * @return array
     */
    private function getIcons()
    {
        $icons = [];

        try {
            CModule::IncludeModule('highloadblock');
            /** @var Bitrix\Main\ORM\Data\DataManager $hlEntity */
            $hlEntity = HighloadBlockTable::compileEntity('Icons')->getDataClass();
            $rows = $hlEntity::getList([
                'select' => ['UF_FILE', 'UF_NAME', 'UF_XML_ID'],
                'order'  => ['UF_NAME' => 'ASC'],
            ]);
            while ($row = $rows->fetch()) {
                $icons[$row['UF_NAME']] = $row['UF_XML_ID'];
            }
        } catch (Exception $e) {
        }

        return $icons;
    }

    /**
     * @param $fileList
     * @param $fileType
     * @param string $filePath
     * @return string
     */
    private function getFileUrl($fileList, $fileType, $filePath = self::FILE_PATH)
    {
        $fileName = '';

        foreach ($fileList as $file) {
            if (
                ((int)$file['as_publish'] == -1)
                && trim($file['as_type']) == $fileType
                && array_key_exists('as_language', $file)
            ) {
                if (
                    !$file['as_language']
                    || $file['as_language'] == $this->langCode
                ) {
                    $fileName = $file['as_file'];
                    break;
                }
            }
        }

        if ($fileName) {
            return $filePath . $fileName;
        }
        return '';
    }

    /**
     * Возврощает массив файлов на всех языках определеного типа
     * @param $fileList
     * @param $fileType
     * @param string $filePath
     * @return array
     */
    public function getAllLangFile($fileList, $fileType, $filePath = self::FILE_PATH)
    {
        $result = [];
        foreach ($fileList as $file) {
            if (
                ((int)$file['as_publish'] == -1)
                && trim($file['as_type']) == $fileType
                && array_key_exists('as_language', $file)
            ) {
                $result[] = $filePath . $file['as_file'];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getCurrentProductList()
    {

        $rows = CIBlockElement::GetList([],
            [
                'IBLOCK_ID' => $this->iBlockId,
            ],
            false,
            false,
            [
                'ID',
                'CODE'
            ]
        );

        $products = [];
        while ($row = $rows->GetNext()) {
            $products[trim(strtoupper($row['CODE']))] = $row['ID'];
        }

        return $products;
    }

    /**
     * Обновляет списков свойств раздела в умном фильтре
     * @param $items
     */
    public function updateSectionFilters($items)
    {
        $iBlockProperties = $this->getIBlockProperties();
        foreach ($items as $parentSection => $sections) {
            foreach ($sections as $section => $properties) {
                $sectionId = $this->getSectionIdByCode($section, $parentSection);
                /**
                 * удаляем все свойсва раздела
                 */
                CIBlockSectionPropertyLink::DeleteBySection($sectionId);

                /**
                 * Добавляем свойсва разделу
                 */
                foreach ($properties as $property) {
                    if ($property) {
                        CIBlockSectionPropertyLink::add(
                            $sectionId,
                            preg_replace("/[^0-9a-z-_]/uim", '', $iBlockProperties[$property]),
                            ['SMART_FILTER' => 'Y']
                        );
                    }
                }
            }
        }
    }

    /**
     * Возвращает id раздела по коду
     * @param $code символный код раздела
     * @param $parentCode символный код родительского раздела
     * @return string
     */
    public function getSectionIdByCode($code, $parentCode)
    {
        $sectionId = '';
        $sections = SectionTable::getList([
            'select' => [
                'ID',
                'PARENT_SECTION_CODE' => 'PARENT_SECTION.CODE'
            ],
            'filter' => [
                'IBLOCK_ID'           => $this->iBlockId,
                'CODE'                => $code,
                'PARENT_SECTION_CODE' => $parentCode
            ]
        ]);
        if ($section = $sections->fetch()) {
            $sectionId = $section['ID'];
        }
        return $sectionId;
    }

    /**
     * Возвращает список свойств инфоблока
     * @return array
     */
    private function getIBlockProperties()
    {
        $properties = CIBlockProperty::GetList([], [
            'ACTIVE'    => 'Y',
            'IBLOCK_ID' => $this->iBlockId
        ]);

        $iBlockProperties = [];
        while ($property = $properties->GetNext()) {
            $iBlockProperties[$property['CODE']] = $property['ID'];
        }
        return $iBlockProperties;
    }
}